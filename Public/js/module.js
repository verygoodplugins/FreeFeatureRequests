/**
 * Module's JavaScript.
 */

function featureRequestsInitLink()
{
	$(document).ready(function() {

		$(".featurerequests-search-form:visible:first").submit(function(e){
			featureRequestsSearch($(".featurerequests-search:visible:first"));
			return false;
		});

		$(".featurerequests-search:visible:first").click(function(e){
			featureRequestsSearch($(this));
		});

		$(".modal:visible .featurerequests-link-switch").click(function(e){
			$('.featurerequests-link-container').toggleClass('hidden');
		});

		$(".modal:visible .featurerequests-form:first").submit(function(e){
			alert('preventDefault');
			e.preventDefault();
		});

		$(".modal:visible .featurerequests-create:first").click(function(e){
			var button = $(this);
			button.button('loading');

			data = new FormData();
	        var form = $('.featurerequests-form:visible:first').serializeArray();
	        for (var field in form) {
	        	data.append(form[field].name, form[field].value);
	        }
	        data.append('action', 'create_request');
	        data.append('conversation_id', getGlobalAttr('conversation_id'));

			fsAjax(
				data, 
				laroute.route('freefeaturerequests.ajax'),
				function(response) {
					button.button('reset');
					if (isAjaxSuccess(response)) {
						window.location.href = '';
					} else {
						showAjaxError(response);
					}
				}, true,
				function(response) {
					showFloatingAlert('error', Lang.get("messages.ajax_error"));
					ajaxFinish();
				}, {
					cache: false,
					contentType: false,
					processData: false
					//type: 'POST'
				}
			);
			return false;
		});
		$('#subscribe-emails').parents('.modal').removeAttr('tabindex');
		$('#subscribe-emails').select2(	{
			width: '100%',
			ajax: {
				url: laroute.route('freefeaturerequests.subscribers'),
				delay: 1000,
				data: function (params) {
					return {
						q: params.term
					};
				},
				processResults: function (data) {
					// Transforms the top-level key of the response object from 'items' to 'results'
					return {
						results: data.subscribers
					};
				}
			}
		});
	});
}

function featureRequestsSearch(button)
{
	var container = $('.featurerequests-remote-request:visible:first');
	var input = $(".featurerequests-search-q:visible:first");
	var not_found_text = $(".featurerequests-not-found:first");

	button.button('loading');
	input.attr('disabled', 'disabled');
	not_found_text.addClass('hidden');
	container.html('');

	fsAjax(
		{
			action: 'search',
			q: input.val()
		}, 
		laroute.route('freefeaturerequests.ajax'),
		function(response) {
			button.button('reset');
			input.removeAttr('disabled');
			if (isAjaxSuccess(response)) {
				var html = '';
				var text_link_request = button.attr('data-text-link');
				if (response.requests.length) {
					for (var i in response.requests) {
						var request = response.requests[i];

						html += '<div class="row featurerequests-search-item">';
						html += '<div class="col-sm-9">';
						html += '<a href="'+request.url+'" target="_blank" class="text-large"><span class="featurerequests-search-summary">'+htmlEscape(request.summary)+'</span></a>';
						html += ' <small class="featurerequests-status-name">('+htmlEscape(request.status)+')</small>';
						html += '<p>'+htmlEscape(request.description)+'</p>';
						html += '</div>';
						html += '<div class="col-sm-3">';
						html += '<button type="button" class="btn btn-xs btn-default featurerequests-btn-link" data-loading-text="'+text_link_request+'…" data-request-key="'+request.key+'" data-request-status="'+request.status+'" data-request-url="'+request.url+'" data-request-category="'+request.category+'">'+text_link_request+'</button>';
						html += '</div>';
						html += '</div>';
						if (i != response.requests.length-1) {
							html += '<hr/>';
						}
					}
				} else {
					not_found_text.removeClass('hidden');
				}
				container.html(html);
			} else {
				container.html('');
				showAjaxError(response);
			}

			// Listeners
			$(".featurerequests-btn-link").click(function(e){
				var button = $(this);
				button.button('loading');

				fsAjax(
					{
						action: 'link',
						conversation_id: getGlobalAttr('conversation_id'),
						request_key: button.attr('data-request-key'),
						request_url: button.attr('data-request-url'),
						request_category: button.attr('data-request-category'),
						request_status: button.attr('data-request-status'),
						request_summary: button.parents('.featurerequests-search-item:first').children().find('.featurerequests-search-summary:first').html()
					}, 
					laroute.route('freefeaturerequests.ajax'),
					function(response) {
						button.button('reset');
						if (isAjaxSuccess(response)) {
							window.location.href = '';
							//$('.modal').modal('hide');
						} else {
							showAjaxError(response);
						}
					}, true
				);
			});
		}, true
	);
}

function featureRequestsInit()
{
	$(document).ready(function() {

		$(".featurerequests-remove").click(function(e){
			var button = $(this);
			var container = button.parents('li:first');
			container.hide();
			fsAjax(
				{
					action: 'unlink_request',
					conversation_id: getGlobalAttr('conversation_id'),
					feature_request_id: container.attr('data-feature-request-id'),
				},
				laroute.route('freefeaturerequests.ajax'),
				function(response) {
					button.button('reset');
					if (isAjaxSuccess(response)) {
						
					} else {
						showAjaxError(response);
						container.show();
					}
				}, true,
				function(response) {
					showFloatingAlert('error', Lang.get("messages.ajax_error"));
					ajaxFinish();
					container.show();
				}
			);
		});
	});
}
