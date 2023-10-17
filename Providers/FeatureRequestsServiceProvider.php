<?php

namespace Modules\FreeFeatureRequests\Providers;

use App\Conversation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\FreeFeatureRequests\Entities\FeatureRequest;
use Modules\FreeFeatureRequests\Entities\FeatureRequestConversation;

define('SFR_MODULE', 'freefeaturerequests');

class FeatureRequestsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;
    const API_METHOD_GET = 'GET';
    const API_METHOD_POST = 'POST';
    const API_METHOD_DELETE = 'DELETE';

    public static $meta = null;

    protected static $disable_faster_search = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }
    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Add module's CSS file to the application layout.
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(SFR_MODULE).'/css/module.css';
            return $styles;
        });

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(SFR_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(SFR_MODULE).'/js/module.js';
            return $javascripts;
        });
        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections[SFR_MODULE] = ['title' => __('Feature Requests'), 'icon' => 'list-alt', 'order' => 500];

            return $sections;
        }, 30);

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {

            if ($section != SFR_MODULE) {
                return $settings;
            }
            $settings['freefeaturerequests.wordpress_hostname'] = config('freefeaturerequests.wordpress_hostname');
            $settings['freefeaturerequests.wordpress_username'] = config('freefeaturerequests.wordpress_username');
            $settings['freefeaturerequests.wordpress_api_token'] = config('freefeaturerequests.wordpress_api_token');
            $settings['freefeaturerequests.helpscout_api_token'] = config('freefeaturerequests.helpscout_api_token');

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {

            if ($section != SFR_MODULE) {
                return $params;
            }

            $wordpress_auth_error = '';
            // Get rooms and test API credentials.
            if (config('freefeaturerequests.wordpress_hostname') && config('freefeaturerequests.wordpress_username') && config('freefeaturerequests.wordpress_api_token')) {
                // Check credentials.
                $test_response = self::apiWordPressCall('wp/v2/users/me', [], self::API_METHOD_GET);

                if (!empty($test_response['message'])) {
                    \Helper::log('feature_requests_errors', 'Error occurred checking GravityKit API credentials: '.json_encode($test_response) ?? '');
                }

                if (!isset($test_response['code']) && (!isset($test_response['status']) || $test_response['status'] != 'error')) {
                    \Option::set('freefeaturerequests.wordpress_active', true);
                    $wordpress_auth_error = '';
                } else {
                    \Option::set('freefeaturerequests.wordpress_active', false);
                    if (!empty($test_response['message'])) {
                        $wordpress_auth_error = $test_response['message'];
                    } else {
                        $wordpress_auth_error = __('Unknown GravityKit API error occurred.');
                    }
                }
            } elseif (\Option::get('freefeaturerequests.wordpress_active')) {
                \Option::set('freefeaturerequests.wordpress_active', false);
            }

            $params['template_vars'] = [
                'wordpress_auth_error'       => $wordpress_auth_error
            ];

            $params['settings'] = [
                'freefeaturerequests.wordpress_hostname' => [
                    'env' => 'FEATURE_REQUEST_HOSTNAME',
                ],
                'freefeaturerequests.wordpress_username' => [
                    'env' => 'FEATURE_REQUEST_USERNAME',
                ],
                'freefeaturerequests.wordpress_api_token' => [
                    'env' => 'FEATURE_REQUEST_API_TOKEN',
                ]
            ];

            return $params;
        }, 20, 2);

        // Settings view name.
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != SFR_MODULE) {
                return $view;
            } else {
                return 'freefeaturerequests::settings';
            }
        }, 20, 2);

        // After saving settings.
        \Eventy::addFilter('settings.after_save', function($response, $request, $section, $settings) {
            if ($section != SFR_MODULE) {
                return $response;
            }
            return $response;

        }, 20, 4);

        // Sidebar.
        \Eventy::addAction('conversation.after_prev_convs', function($customer, $conversation, $mailbox) {
            $requests = FeatureRequest::select(['feature_requests.*'])
                ->leftJoin('feature_request_conversation', function ($join) {
                    $join->on('feature_request_conversation.feature_request_id', '=', 'feature_requests.id');
                })
                ->where('feature_request_conversation.conversation_id', $conversation->id)
                ->get();

            echo \View::make('freefeaturerequests::partials/sidebar', [
                'requests'         => $requests,
                'conversation'         => $conversation,
            ])->render();
        }, 12, 3);

        // Custom menu in conversation.
        \Eventy::addAction('conversation.customer.menu', function($customer, $conversation) {
            ?>
            <li role="presentation" class="col3-hidden"><a data-toggle="collapse" href=".featurerequests-collapse-sidebar" tabindex="-1" role="menuitem">Feature Requests</a></li>
            <?php
        }, 12, 2);

        /**
         * If FasterSearch is active, disable it while performing a search for Feature Requests.
         * FasterSearch will be re-enabled in the `search.conversations.apply_filters` filter.
         */
        \Eventy::addFilter('search.filters', function($filters) {

            // If Faster Search isn't active, no need to continue.
            if ( ! \Module::isActive('fastersearch') ) {
                return $filters;
            }

            $is_feature_request_search = ! empty( $filters['feature_request'] );

            \Option::set('fastersearch.active', ! $is_feature_request_search );

            self::$disable_faster_search = $is_feature_request_search;

            return $filters;
        }, 100, 1);

        // Filter by feature_request in search
        \Eventy::addFilter('search.conversations.apply_filters', function($query_conversations, $filters) {

            if ( empty($filters['feature_request'])) {
                return $query_conversations;
            }

            if (filter_var($filters['feature_request'], FILTER_VALIDATE_URL)) {
                $query_conversations
                    ->join('feature_request_conversation', function ($join) {
                        $join->on('conversations.id', '=', 'feature_request_conversation.conversation_id');
                    })
                    ->join('feature_requests', function ($join) {
                        $join->on('feature_requests.id', '=', 'feature_request_conversation.feature_request_id');
                    })
                    ->where('feature_requests.url', $filters['feature_request']);
            } else {
                $query_conversations
                    ->join('feature_request_conversation', function ($join) {
                        $join->on('conversations.id', '=', 'feature_request_conversation.conversation_id');
                    })
                    ->join('feature_requests', function ($join) {
                        $join->on('feature_requests.id', '=', 'feature_request_conversation.feature_request_id');
                    })
                    ->where('feature_requests.key', $filters['feature_request'])
                    ->orWhere('feature_requests.category', $filters['feature_request'])
                    ->orWhere('feature_requests.summary', $filters['feature_request']);
            }

            /** Restore FasterSearch if it was active before the search. */
            if ( self::$disable_faster_search ) {
                \Option::set('fastersearch.active', true);
            }

            return $query_conversations;
        }, 20, 2);

        // Add Feature Requests to search filters.
        \Eventy::addFilter('search.filters_list', function($filters_list, $mode, $filters, $q) {

            if ($mode != Conversation::SEARCH_MODE_CONV) {
                return $filters_list;
            }

            // Add after subject.
            foreach ($filters_list as $i => $filter) {
                if ($filter == 'subject') {
                    array_splice($filters_list, $i+1, 0, 'feature_request');
                    break;
                }
            }

            return $filters_list;
        }, 20, 4);

        // Add Feature Requests to search filters.
        \Eventy::addAction('search.display_filters', function($filters, $filters_data, $mode) {
            ?>
            <div class="col-sm-6 form-group <?php if (isset($filters['feature_request'])): ?> active <?php endif ?>" data-filter="feature_request">
                <label><?php echo __('Feature Request') ?> <b class="remove" data-toggle="tooltip" title="<?php echo __('Remove filter') ?>">×</b></label>
                <input type="text" name="f[feature_request]" value="<?php echo ($filters['feature_request'] ?? '') ?>" placeholder="" class="form-control" <?php if (empty($filters['feature_request'])): ?> disabled <?php endif ?>>
            </div>
            <?php
        }, 20, 3);
    }

    public static function getMetas()
    {
        if (self::$meta === null) {
            self::$meta = \Option::get('freefeaturerequests.meta');
        }
        if (!is_array(self::$meta)) {
            self::$meta = [];
        }
        return self::$meta;
    }

    public static function getMeta($key)
    {
        $meta = self::getMetas();

        return self::$meta[$key] ?? null;
    }

    public static function setMeta($key, $value)
    {
        $meta = self::getMetas();

        if (!is_array($meta)) {
            $meta = [];
        }
        $meta[$key] = $value;

        self::$meta = $meta;

        \Option::set('freefeaturerequests.meta', $meta);
    }

    public static function setMetas($meta)
    {
        self::$meta = $meta;

        \Option::set('freefeaturerequests.meta', $meta);
    }
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('freefeaturerequests.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'freefeaturerequests'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/freefeaturerequests');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/freefeaturerequests';
        }, \Config::get('view.paths')), [$sourcePath]), 'freefeaturerequests');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/freefeaturerequests');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'freefeaturerequests');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'freefeaturerequests');
        }
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    public static function apiWordPressCall($url, $params, $http_method = self::API_METHOD_POST)
    {
        $response = [
        ];

        $api_url = config('freefeaturerequests.wordpress_hostname').'/wp-json/'.$url;
        if (($http_method == self::API_METHOD_GET || $http_method == self::API_METHOD_DELETE)
            && !empty($params)
        ) {
            $api_url .= '?'.http_build_query($params);
        }
        try {
            $ch = curl_init($api_url);

            $headers = [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic '.base64_encode(config('freefeaturerequests.wordpress_username').':'.config('freefeaturerequests.wordpress_api_token')),
            ];

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);
            if ($http_method == self::API_METHOD_POST) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 40);

            $json_response = curl_exec($ch);

            $response = json_decode($json_response, true);

            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                throw new \Exception(curl_error($ch), 1);
            }

            curl_close($ch);

            if (empty($response) && $status != 204 && $status != 200 && $status != 201) {
                throw new \Exception(__('Empty API response. Check your GravityKit credentials. HTTP status code: :status', ['status' => $status]), 1);
            } elseif ($status == 204) {
                return [
                    'status' => 'success',
                ];
            }

        } catch (\Exception $e) {
            \Helper::log('feature_requests_errors', 'API error: '.$e->getMessage().'; Response: '.json_encode($response).'; Method: '.$url.'; Parameters: '.json_encode($params));

            return [
                'status' => 'error',
                'message' => __('API call error.').' '.$e->getMessage()
            ];
        }

        return $response;
    }
}
