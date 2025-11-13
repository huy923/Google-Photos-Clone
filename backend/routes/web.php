<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Google Photos Clone API',
        'api' => '/api',
        'version' => '1.0.0',
        'status' => 'active'
    ]);
});
