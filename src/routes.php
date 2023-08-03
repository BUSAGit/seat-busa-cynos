<?php

Route::group([ 
    'namespace' => 'Helious\SeatBusaCynos\Http\Controllers\Api',
    'prefix' => 'api',
    'middleware' => ['api.request', 'api.auth'],
], function () {
    
    Route::group(['namespace' => 'v2', 'prefix' => 'v2'], function () {
        Route::group(['prefix' => 'cynos'], function () {
            Route::get('/{desto}/{maxJumps}')->uses('CynosController@index');
            Route::get('/{desto}')->uses('CynosController@index');
        });
        
        Route::group(['prefix' => 'assetsCheck'], function () {
            Route::get('/{desto}/{lookingForGroup}')->uses('AssetCheckController@index');
        });
    });

});