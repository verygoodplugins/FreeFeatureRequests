<div class="conv-sidebar-block">
    <div class="panel-group accordion accordion-empty">
        <div class="panel panel-default" id="featurerequests-sidebar">
			<div class="panel-heading">
			    <h4 class="panel-title">
			        <a data-toggle="collapse" href=".featurerequests-collapse-sidebar">
						Feature Requests
			            <b class="caret"></b>
			        </a>
			    </h4>
			</div>
			<div class="featurerequests-collapse-sidebar panel-collapse collapse in">
			    <div class="panel-body">
			        <div class="sidebar-block-header2"><strong>Feature Requests</strong> (<a data-toggle="collapse" href=".featurerequests-collapse-sidebar">{{ __('close') }}</a>)</div>

		            @if (count($requests))
			            <ul class="sidebar-block-list featurerequests-sidebar">
		                    @foreach($requests as $request)
	                            <li data-feature-request-id="{{ $request->id }}">
									<span class="pull-right featurerequests-remove"><i class="glyphicon glyphicon-remove"></i></span><span class="featurerequests-search-link"><a href="{{route('conversations.search', ['f[feature_request]' => $request->getUrl()])}}" target="_blank"><i class="glyphicon glyphicon-search"></i></a></span>&nbsp;<a href="{{ $request->getUrl() }}" target="_blank">{{ $request->getTitle() }}</a> <small class="featurerequests-status-name">@if (!empty($request->status) && !empty($request->getStatusName()))({{ $request->getStatusName() }})@endif</small>
	                            </li>
		                    @endforeach
	                    </ul>
					@endif

			        <div class="margin-top-10">
			            <a href="{{ route('freefeaturerequests.ajax_html', ['action' => 'link_request', 'conv_subject' => $conversation->subject]) }}" data-trigger="modal" data-modal-title="{{ __('Link Feature Request') }}" data-modal-no-footer="true" data-modal-on-show="featureRequestsInitLink" class="btn btn-default btn-block" id="featurerequests-link"><small class="glyphicon glyphicon-link"></small> {{ __("Link Feature Request") }}</a>
			        </div>

			    </div>
			</div>

        </div>
    </div>
</div>

@section('javascript')
    @parent
	featureRequestsInit();
@endsection
