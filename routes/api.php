<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:api');

Route::apiResource('users', UserController::class)->only(['index', 'store', 'show']);

Route::middleware('auth:api')->group(function () {
    Route::apiResource('users', UserController::class)->only(['update', 'destroy']);
    Route::get('me', [UserController::class, 'me']);
});
