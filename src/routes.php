<?php

Route::group([ 
    'namespace' => 'Helious\SeatBusaCynos\Http\Controllers',
    'prefix' => 'api',
    'middleware' => ['api.request', 'api.auth'],
], function () {

    Route::get('/cynos', [
        'uses' => 'CynosController@index',
    ]);

});