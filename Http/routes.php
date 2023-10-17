<?php

Route::group(['middleware' => 'web', 'prefix' => 'freefeaturerequests', 'namespace' => 'Modules\FreeFeatureRequests\Http\Controllers'], function()
{
    Route::get('/', 'FeatureRequestsController@index');
    Route::get('/ajax-html/{action}', ['uses' => 'FeatureRequestsController@ajaxHtml', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('freefeaturerequests.ajax_html');
    Route::post('/ajax', ['uses' => 'FeatureRequestsController@ajax', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin'], 'laroute' => true])->name('freefeaturerequests.ajax');
});
