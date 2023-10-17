<form class="form-horizontal margin-top margin-bottom" method="POST" action="">
    {{ csrf_field() }}
    @if ($wordpress_auth_error)
        <div class="alert alert-danger">
            <strong>{{ __('WordPress API authentication error') }}</strong><br/>{{ $wordpress_auth_error }}
        </div>
    @endif
    <div class="form-group margin-bottom">
        <label class="col-sm-2 control-label">{{ __('Integration Status with WordPress') }}</label>
        <div class="col-sm-6">
            <label class="control-label">
                @if (\Option::get('freefeaturerequests.wordpress_active'))
                    <strong class="text-success"><i class="glyphicon glyphicon-ok"></i> {{ __('Active') }}</strong>
                @else
                    <strong class="text-warning">{{ __('Inactive') }}</strong>
                @endif
            </label>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __('WordPress Site URL') }}</label>
        <div class="col-sm-6">
            <input type="text" class="form-control input-sized-lg" name="settings[freefeaturerequests.wordpress_hostname]" value="{{ $settings['freefeaturerequests.wordpress_hostname'] }}">
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __('WordPress Username') }}</label>
        <div class="col-sm-6">
            <input type="text" class="form-control input-sized-lg" name="settings[freefeaturerequests.wordpress_username]" value="{{ $settings['freefeaturerequests.wordpress_username'] }}">
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __('WordPress API Token') }}</label>
        <div class="col-sm-6">
            <input type="password" class="form-control input-sized-lg" name="settings[freefeaturerequests.wordpress_api_token]" value="{{ $settings['freefeaturerequests.wordpress_api_token'] }}" autocomplete="new-password">
            <div class="form-help">
                <a href="https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/" target="_blank">{{ __('How to get an API Token in WordPress?') }}</a>
            </div>
        </div>
    </div>
    <div class="form-group margin-top margin-bottom">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>
