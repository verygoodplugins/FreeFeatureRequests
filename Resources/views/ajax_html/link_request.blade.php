<div class="row-container">
	<div class="featurerequests-link-container">
		<form class="row featurerequests-search-form">
			<div class="col-sm-3">
				<div class="form-group">
					<button class="btn btn-primary featurerequests-link-switch" type="button">{{ __('New Request') }}</button>
				</div>
			</div>
			<div class="col-sm-9">
				<div class="form-group">
					<div class="input-group">
				        <input type="text" class="form-control featurerequests-search-q" name="q">
				        <span class="input-group-btn">
				            <button class="btn btn-default featurerequests-search" type="button" data-loading-text="{{ __('Search') }}…" data-text-link="{{ __('Link Feature Request') }}">{{ __('Search') }}</button>
				        </span>
				    </div>
				    <div class="featurerequests-not-found form-help hidden">{{ __('No requests found') }}</div>
				</div>
			</div>
		</form>
		<div class="featurerequests-remote-request margin-top-10">
		</div>
	</div>
	<div class="featurerequests-link-container hidden">
		<form class="form-horizontal featurerequests-form">

            <div class="form-group">
                <div class="col-sm-8 col-sm-offset-2">
	                <button type="button" class="btn btn-bordered featurerequests-link-switch"><i class="glyphicon glyphicon-chevron-left featurerequests-link-back"></i></button>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Status') }}</label>
                <div class="col-sm-8">
                    <select name="status" class="form-control" required autofocus>
                        @foreach ($statuses as $statue)
                        	<option value="{{ $statue['key'] }}">{{ $statue['value'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

			<div class="form-group">
				<label class="col-sm-2 control-label">{{ __('Category') }}</label>
				<div class="col-sm-8">
					<select name="category" class="form-control" required>
						@foreach ($categories as $category)
							<option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
						@endforeach
					</select>
				</div>
			</div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Title') }}</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" name="summary" value="{{ $conv_subject }}" required/>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Description') }}</label>
                <div class="col-sm-8">
                    <textarea class="form-control" name="description" rows="4" placeholder="{{ __('(optional)') }}"></textarea>
                </div>
            </div>
			<div class="form-group">
				<label class="col-sm-2 control-label">{{ __('Subscribe Emails') }}</label>
				<div class="col-sm-8">
					<select class="form-control" name="subscriber" id="subscribe-emails"></select>
				</div>
			</div>
            <div class="form-group">
                <div class="col-sm-8 col-sm-offset-2">
	                <button type="submit" class="btn btn-primary featurerequests-create" data-loading-text="{{ __('Create Feature Request') }}…">
	                    {{ __('Create Request') }}
	                </button>
                </div>
            </div>

		</form>
	</div>
</div>
