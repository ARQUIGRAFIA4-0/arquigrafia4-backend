<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
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
use App\Http\Controllers\VRACore\VRACMeasurementController;
use App\Http\Controllers\VRACore\VRACRightController;
use App\Http\Controllers\VRACore\VRACSourceController;
use App\Http\Controllers\VRACore\VRACStateEditionController;
use App\Http\Controllers\VRACore\VRACStylePeriodController;
use App\Http\Controllers\VRACore\VRACSubjectController;
use App\Http\Controllers\VRACore\VRACTechniqueController;
use App\Http\Controllers\VRACore\VRACTextRefController;
use App\Http\Controllers\VRACore\VRACTitleController;
use App\Http\Controllers\VRACore\VRACWorkTypeController;
use Illuminate\Support\Facades\Route;

Route::apiResource('users', UserController::class)->only(['index', 'store', 'show']);
Route::apiResource('profiles', ProfileController::class)->only(['show']);
Route::get('profiles/by-user-id/{userId}', [ProfileController::class, 'getByUserId']);

Route::middleware('auth:api')->group(function () {
    Route::apiResource('users', UserController::class)->only(['update', 'destroy']);
    Route::apiResource('profiles', ProfileController::class)->only(['store', 'update']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::middleware('throttle:6,1')->group(function () {
    Route::post('/account-verification-email', [AuthController::class, 'sendVerificationEmail'])
        ->name('verification.email');
    Route::post('/verify-account', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify');
    Route::post('/forgot-password-email', [AuthController::class, 'passwordResetEmail'])
        ->middleware(['guest'])->name('password.email');
    Route::post('/verify-password-reset', [AuthController::class, 'verifyPasswordReset'])
        ->middleware(['guest'])->name('password.verify');
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
Route::apiResource('vrac-measurements', VRACMeasurementController::class);
Route::apiResource('vrac-rights', VRACRightController::class);
Route::apiResource('vrac-sources', VRACSourceController::class);
Route::apiResource('vrac-state-editions', VRACStateEditionController::class);
Route::apiResource('vrac-style-periods', VRACStylePeriodController::class);
Route::apiResource('vrac-subjects', VRACSubjectController::class);
Route::apiResource('vrac-techniques', VRACTechniqueController::class);
Route::apiResource('vrac-text-refs', VRACTextRefController::class);
Route::apiResource('vrac-titles', VRACTitleController::class);
Route::apiResource('vrac-work-types', VRACWorkTypeController::class);

// Image uploads
use App\Http\Controllers\ImageController;

Route::post('images', [ImageController::class, 'store']);
