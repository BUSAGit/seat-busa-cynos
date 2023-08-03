<?php

Route::group([ 
    'namespace' => 'Helious\SeatBusaCynos\Http\Controllers',
    'prefix' => 'api',
    'middleware' => ['api.request', 'api.auth'],
], function () {
    
    Route::group(['namespace' => 'v2', 'prefix' => 'v2'], function () {
        Route::group(['prefix' => 'cynos'], function () {
            Route::post('/')->uses('CynosController@index');
        });
    });

});