<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\VRACore\VRACAgentController;
use App\Http\Controllers\VRACore\VRACAgentRoleController;
use App\Http\Controllers\VRACore\VRACContributorNameController;
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

// VRACore
Route::apiResource('vrac-agents', VRACAgentController::class);
Route::apiResource('vrac-agent-roles', VRACAgentRoleController::class);
Route::apiResource('vrac-contributor-names', VRACContributorNameController::class);
