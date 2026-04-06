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
use App\Http\Controllers\CollectiveController;
use App\Http\Controllers\CollectiveInviteController;
use App\Http\Controllers\CollectiveMemberController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AlbumController;

 // álbumes
Route::get('/albums', [AlbumController::class, 'index']);
Route::get('/albums/{album}', [AlbumController::class, 'show']);
Route::get('/users/{user}/albums', [AlbumController::class, 'getByUser']); 

Route::apiResource('users', UserController::class)->only(['index', 'store', 'show']);
Route::apiResource('profiles', ProfileController::class)->only(['show']);
Route::get('profiles/by-user-id/{userId}', [ProfileController::class, 'getByUserId']);
Route::get('locations/geojson', [LocationController::class, 'geojson']);
Route::apiResource('images', ImageController::class)->only(['index', 'show']);
Route::apiResource('collectives', CollectiveController::class)->only(['index', 'show']);

Route::middleware('auth:api')->group(function () {
    // álbumes
    Route::post('/albums', [AlbumController::class, 'store']);
    Route::put('/albums/{album}', [AlbumController::class, 'update']);
    Route::delete('/albums/{album}', [AlbumController::class, 'destroy']);

    // imágenes dentro de álbum
    Route::put('/albums/{album}/images', [AlbumController::class, 'syncImages']);
    Route::post('/albums/{album}/images', [AlbumController::class, 'addImage']);
    Route::delete('/albums/{album}/images', [AlbumController::class, 'removeImages']);
    //Route::delete('/albums/{album}/images/{image}', [AlbumController::class, 'removeImage']);

    Route::apiResource('users', UserController::class)->only(['update', 'destroy']);
    Route::apiResource('profiles', ProfileController::class)->only(['store', 'update']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::apiResource('images', ImageController::class)->only(['store', 'update', 'destroy']);

    // Collectives
    Route::apiResource('collectives', CollectiveController::class)->only(['store', 'update', 'destroy']);
    Route::post('collectives/join', [CollectiveInviteController::class, 'redeem']);
    Route::get('collectives/{collective}/members', [CollectiveMemberController::class, 'index']);
    Route::put('collectives/{collective}/members/{user}', [CollectiveMemberController::class, 'update']);
    Route::delete('collectives/{collective}/members/{user}', [CollectiveMemberController::class, 'destroy']);
    Route::get('collectives/{collective}/invites', [CollectiveInviteController::class, 'index']);
    Route::post('collectives/{collective}/invites', [CollectiveInviteController::class, 'store']);
    Route::delete('collectives/{collective}/invites/{invite}', [CollectiveInviteController::class, 'destroy']);
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
    Route::post('/change-password-reset', [AuthController::class, 'changePasswordReset'])
        ->middleware(['guest'])->name('password.reset');
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
