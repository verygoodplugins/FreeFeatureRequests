<?php

namespace Modules\FreeFeatureRequests\Http\Controllers;

use App\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\FreeFeatureRequests\Entities\FeatureRequest;
use Modules\FreeFeatureRequests\Entities\FeatureRequestConversation;
use Modules\FreeFeatureRequests\Providers\FeatureRequestsServiceProvider;

class FeatureRequestsController extends Controller
{
	/**
	 * Display a listing of the resource.
	 * @return Response
	 */
	public function index()
	{
		return view('freefeaturerequests::index');
	}

	/**
	 * Ajax controller.
	 */
	public function ajax(Request $request)
	{
		$response = [
			'status' => 'error',
			'msg'    => '', // this is error message
		];

		$statues = FeatureRequestsServiceProvider::getMeta('statuses');
		if (!\Option::get('freefeaturerequests.wordpress_active')) {
			$response['msg'] = 'Please check the API configuration on <a href="'.url('/app-settings/featurerequests').'" target="_blank">setting</a>';
			return \Response::json($response);
		}
		switch ($request->action) {

			case 'search':
				$q = str_replace('"', '\\"', $request->q ?? '');
				$query = $q;
				// First search by key.
				$api_response = FeatureRequestsServiceProvider::apiWordPressCall('wp/v2/cpt_feature_requests', [
					'search' => $query,
					'page' => 1,
					'per_page' => 50,
					'orderby' => 'date',
					'order' => 'desc',
				], FeatureRequestsServiceProvider::API_METHOD_GET);

				if (isset($api_response['status']) && $api_response['status'] == 'error') {
					$response['msg'] = $api_response['message'] ?? '';
				} elseif (!empty($api_response['message'])) {
					\Helper::log('feature_requests_errors', 'Error occurred searching the feature requests: '.'Query: "'. $query . '". Error: '. print_r( $api_response, true ) );
					$response['requests'] = [];
					$response['status'] = 'success';
				} else {
					$requests = [];

					foreach ($api_response as $request) {
						$request_data = [
							'key' => $request['id'],
							'summary' => $request['title']['rendered'],
							'status' => $request['jck_sfr_status'],
							'description' => strip_tags($request['content']['rendered'] ?? ''),
							'url' => $request['link'],
							'category' => 0,
						];
						if (mb_strlen($request_data['description']) > 100) {
							$request_data['description'] = mb_substr($request_data['description'], 0, 100).'…';
						}

						$requests[] = $request_data;
					}

					$response['requests'] = $requests;
					$response['status'] = 'success';
				}
				break;

			case 'link':
				$user = auth()->user();

				$conversation = Conversation::find($request->conversation_id);

				if (!$conversation) {
					$response['msg'] = __('Conversation not found');
				}
				if (!$response['msg'] && !$user->can('update', $conversation)) {
					$response['msg'] = __('Not enough permissions');
				}

				if (empty($response['msg'])) {
					try {
						$feature_request = FeatureRequest::createOrUpdate([
							'key' => $request->request_key,
							'category' => $request->request_category,
							'status' => $request->request_status,
							'url' => $request->request_url,
							'summary' => $request->request_summary,
						]);
					} catch (\Exception $e) {
						\Helper::logException($e, '[FreeFeatureRequests]');
					}
					if (empty($feature_request)) {
						$response['msg'] = __('Error occurred creating a feature_requests');
					}
					if (empty($response['msg'])) {
						$feature_request_conversation = new FeatureRequestConversation();
						$feature_request_conversation->feature_request_id = $feature_request->id;
						$feature_request_conversation->conversation_id = $conversation->id;

						try {
							$feature_request_conversation->save();
						} catch (\Exception $e) {
							\Helper::log('feature_requests_errors', 'Error occurred creating feature request conversation: '. print_r( $e->getMessage(), true ) );
						}

						$response['status'] = 'success';
					}
				}
				break;

			case 'create_request':

				$user = auth()->user();

				$conversation = Conversation::find($request->conversation_id);

				if (!$conversation) {
					$response['msg'] = __('Conversation not found');
				}
				if (!$response['msg'] && !$user->can('update', $conversation)) {
					$response['msg'] = __('Not enough permissions');
				}

				$api_response = FeatureRequestsServiceProvider::apiWordPressCall('wp/v2/cpt_feature_requests', [
					'title' => $request->summary,
					'content' => $request->description,
					'request_category' => $request->category,
					'jck_sfr_status' => $request->status,
					'status' => 'publish',
					'author' => $request->subscriber,
				]);

				if (!empty($api_response['id'])) {
					try {
						$feature_request = FeatureRequest::createOrUpdate([
							'key' => $api_response['id'],
							'status' => $request->status,
							'summary' => $request->summary,
							'category' => $request->category,
							'url' => $api_response['link'],
						]);
					} catch (\Exception $e) {
						\Helper::logException($e, '[FreeFeatureRequests]');
					}
					if (empty($feature_request)) {
						$response['msg'] = __('Error occurred creating a feature request');
					}
					if (empty($response['msg'])) {
						$feature_request_conversation = new FeatureRequestConversation();
						$feature_request_conversation->feature_request_id = $feature_request->id;
						$feature_request_conversation->conversation_id = $conversation->id;

						try {
							$feature_request_conversation->save();
						} catch (\Exception $e) {

						}
						$response['status'] = 'success';
					}
				} elseif (!empty($api_response['message'])) {
					$response['msg'] = $api_response['message'];
				} elseif (!empty($api_response['errors'])) {
					\Helper::log('feature_requests_errors', 'Error occurred creating a feature request via API: '. print_r( $api_response, true ). PHP_EOL );
					$response['msg'] = json_encode($api_response['errors']);
				}
				break;

			case 'unlink_request':
				$user = auth()->user();

				$conversation = Conversation::find($request->conversation_id);

				if (!$conversation) {
					$response['msg'] = __('Conversation not found');
				}
				if (!$response['msg'] && !$user->can('update', $conversation)) {
					$response['msg'] = __('Not enough permissions');
				}

				if (empty($response['msg'])) {
					FeatureRequestConversation::where('conversation_id', $conversation->id)
						->where('feature_request_id', $request->feature_request_id)
						->delete();

					$response['status'] = 'success';
				}
				break;
			default:
				$response['msg'] = 'Unknown action';
				break;
		}

		if ($response['status'] == 'error' && empty($response['msg'])) {
			$response['msg'] = 'Unknown error occured';
		}

		return \Response::json($response);
	}

	/**
	 * Ajax html.
	 */
	public function ajaxHtml(Request $request)
	{
		$response = [
			'status' => 'error',
			'msg'    => '', // this is error message
		];

		switch ($request->action) {
			case 'link_request':
				$categories = [];
				$statuses = [];
				$api_response = FeatureRequestsServiceProvider::apiWordPressCall('wp/v2/request_category', [
					'page' => 1,
					'per_page' => 50,
					'orderby' => 'name'
				], FeatureRequestsServiceProvider::API_METHOD_GET);
				if ((isset($api_response['status']) && $api_response['status'] == 'error') || !empty($api_response['message'])) {
					\Helper::log('feature_requests_errors', 'Error occurred searching the request categories: '. print_r( $api_response, true ) );
				} else {
					foreach ($api_response as $category) {
						$category_data = [
							'id' => $category['id'],
							'name' => $category['name'],
						];
						$categories[] = $category_data;
					}
				}

				$api_response = FeatureRequestsServiceProvider::apiWordPressCall('wp/v2/cpt_feature_requests/statuses', [
					'page' => 1,
					'per_page' => 50,
					'orderby' => 'name'
				], FeatureRequestsServiceProvider::API_METHOD_GET);
				if ((isset($api_response['status']) && $api_response['status'] == 'error') || !empty($api_response['message'])) {
					\Helper::log('feature_requests_errors', 'Error occurred searching the request statuses: '. print_r( $api_response, true ) );
				} else {
					foreach (array_keys($api_response) as $status) {
						$statuses[] = [
							'key' => $status,
							'value' => $api_response[$status],
						];
					}

					$meta = FeatureRequestsServiceProvider::getMetas();
					$meta['statuses'] = $statuses;
					// Save metas.
					FeatureRequestsServiceProvider::setMetas($meta);
				}
				return view('freefeaturerequests::ajax_html/link_request', [
					'statuses' => $statuses,
					'categories' => $categories,
					'conv_subject' => $request->conv_subject ?? '',
				]);
			case 'subscribers':
				$subscribers = [];
				$q = str_replace('"', '\\"', $request->q ?? '');
				$query = $q;
				// First search by key.
				$api_response = FeatureRequestsServiceProvider::apiWordPressCall('wp/v2/users', [
					'search' => $query,
					'page' => 1,
					'per_page' => 50,
					'context' => 'edit',
				], FeatureRequestsServiceProvider::API_METHOD_GET);

				if (isset($api_response['status']) && $api_response['status'] == 'error') {
					$response['msg'] = $api_response['message'] ?? '';
				} elseif (!empty($api_response['message'])) {
					\Helper::log('feature_requests_errors', 'Error occurred searching the users in WordPress.'.'Query: "'. $query . '". Error: '. print_r( $api_response, true ) );
				} else {
					foreach ($api_response as $subscriber) {
						$subscribers[] = [
							'id' => $subscriber['id'],
							'text' => $subscriber['email'],
						];
					}
				}
				$response['subscribers'] = $subscribers;
				$response['status'] = 'success';
				return \Response::json($response);
				break;
		}
	}

	/**
	 * Show the form for creating a new resource.
	 * @return Response
	 */
	public function create()
	{
		return view('freefeaturerequests::create');
	}

	/**
	 * Store a newly created resource in storage.
	 * @param  Request $request
	 * @return Response
	 */
	public function store(Request $request)
	{
	}

	/**
	 * Show the specified resource.
	 * @return Response
	 */
	public function show()
	{
		return view('freefeaturerequests::show');
	}

	/**
	 * Show the form for editing the specified resource.
	 * @return Response
	 */
	public function edit()
	{
		return view('freefeaturerequests::edit');
	}

	/**
	 * Update the specified resource in storage.
	 * @param  Request $request
	 * @return Response
	 */
	public function update(Request $request)
	{
	}

	/**
	 * Remove the specified resource from storage.
	 * @return Response
	 */
	public function destroy()
	{
	}
}
