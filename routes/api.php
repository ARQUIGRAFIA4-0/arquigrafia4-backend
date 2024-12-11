<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\VRACore\VRACAgentController;
use App\Http\Controllers\VRACore\VRACAgentRoleController;
use App\Http\Controllers\VRACore\VRACContributorNameController;
use App\Http\Controllers\VRACore\VRACCulturalContextController;
use App\Http\Controllers\VRACore\VRACDateController;
use App\Http\Controllers\VRACore\VRACDescriptionController;
use App\Http\Controllers\VRACore\VRACInscriptionController;
use App\Http\Controllers\VRACore\VRACLocationController;
use App\Http\Controllers\VRACore\VRACLocationNameController;
use App\Http\Controllers\VRACore\VRACMaterialController;
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
Route::apiResource('vrac-cultural-contexts', VRACCulturalContextController::class);
Route::apiResource('vrac-dates', VRACDateController::class);
Route::apiResource('vrac-descriptions', VRACDescriptionController::class);
Route::apiResource('vrac-inscriptions', VRACInscriptionController::class);
Route::apiResource('vrac-location-names', VRACLocationNameController::class);
Route::apiResource('vrac-locations', VRACLocationController::class);
Route::apiResource('vrac-materials', VRACMaterialController::class);
