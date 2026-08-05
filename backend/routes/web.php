<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response(File::get(public_path('index.html')), 200)->header('Content-Type', 'text/html');
});

Route::fallback(function () {
    if (request()->is('api/*')) {
        return response()->json(['success' => false, 'message' => 'Not Found'], 404);
    }
    return response(File::get(public_path('index.html')), 200)->header('Content-Type', 'text/html');
});