<?php

use App\Http\Controllers\ActorController;
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
use App\Http\Controllers\VRACore\VRACWorkController;
use App\Http\Controllers\VRACore\VRACWorkTypeController;
use App\Http\Controllers\CollectiveController;
use App\Http\Controllers\CollectiveJoinRequestController;
use App\Http\Controllers\CollectiveMemberController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommentLikeController;
use App\Http\Controllers\BinomialEvaluationController;
use App\Http\Controllers\ReportController;

// álbumes
Route::get('/albums', [AlbumController::class, 'index']);
Route::get('/albums/{album}', [AlbumController::class, 'show']);
Route::get('/users/{user}/albums', [AlbumController::class, 'getByUser']);
Route::get('/collectives/{collectiveId}/albums', [AlbumController::class, 'getByCollective']);

use App\Http\Controllers\ImageSuggestionController;
use App\Http\Controllers\WorkSuggestionController;


Route::apiResource('users', UserController::class)->only(['index', 'store', 'show']);
Route::apiResource('profiles', ProfileController::class)->only(['show']);
Route::get('profiles/by-user-id/{userId}', [ProfileController::class, 'getByUserId']);
Route::get('locations/geojson', [LocationController::class, 'geojson']);
Route::get('/images/search-suggestions', [ImageController::class, 'searchSuggestions']);
Route::get('locations/geojson/search', [LocationController::class, 'filteredGeojson']);
Route::apiResource('images', ImageController::class)->only(['index', 'show']);
Route::get('/images/{image}/related', [ImageController::class, 'related']);
Route::apiResource('collectives', CollectiveController::class)->only(['index', 'show']);
Route::get('actors', [ActorController::class, 'index']);
Route::get('/images/{imageId}/comments', [CommentController::class, 'index']);

// Binomials
Route::get('/images/{image}/binomials', [BinomialEvaluationController::class, 'index']);
Route::get('/images/{image}/binomials/report', [BinomialEvaluationController::class, 'report']);
Route::get('/images/{image}/binomials/evaluations', [BinomialEvaluationController::class, 'evaluations']);
Route::get('/comments/{commentId}/replies', [CommentController::class, 'replies']);

// álbumes
Route::get('/albums', [AlbumController::class, 'index']);
Route::get('/albums/{album}', [AlbumController::class, 'show']);
Route::get('/albums/{album}/tags', [AlbumController::class, 'tags']);
Route::get('/albums/{album}/stats', [AlbumController::class, 'stats']);
Route::get('/users/{user}/albums', [AlbumController::class, 'getByUser']);

//suggestions
Route::get('/image-suggestions', [ImageSuggestionController::class, 'index']);
Route::get('/image-suggestions/{imageSuggestion}', [ImageSuggestionController::class, 'show']);
Route::get('/work-suggestions', [WorkSuggestionController::class, 'index']);
Route::get('/work-suggestions/{workSuggestion}', [WorkSuggestionController::class, 'show']);

Route::middleware('auth:api')->group(function () {

    Route::middleware('throttle:10,1')->group(function () {
        Route::apiResource('users', UserController::class)->only(['update', 'destroy']);
        Route::apiResource('collectives', CollectiveController::class)->only(['store', 'update', 'destroy']);
    });

    Route::apiResource('profiles', ProfileController::class)->only(['store', 'update']);

    Route::apiResource('vrac-works', VRACWorkController::class)->only(['store', 'update', 'destroy']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::apiResource('images', ImageController::class)->only(['store', 'update', 'destroy']);

    //Comments
    Route::post('/comments', [CommentController::class, 'store'])
        ->middleware('throttle:store-comment');
    Route::patch('/comments/{commentId}', [CommentController::class, 'update'])
        ->middleware('throttle:update-comment');
    Route::delete('/comments/{commentId}', [CommentController::class, 'destroy']);

    // Comment Likes
    Route::post('/comments/{commentId}/like', [CommentLikeController::class, 'store']);

    // Collectives
    Route::get('collectives/{collective}/members', [CollectiveMemberController::class, 'index']);
    Route::put('collectives/{collective}/members/{user}', [CollectiveMemberController::class, 'update']);
    Route::delete('collectives/{collective}/members/{user}', [CollectiveMemberController::class, 'destroy']);

    // álbumes
    Route::post('/albums', [AlbumController::class, 'store']);
    Route::put('/albums/{album}', [AlbumController::class, 'update']);
    Route::delete('/albums/{album}', [AlbumController::class, 'destroy']);
    Route::put('/albums/{album}/images', [AlbumController::class, 'syncImages']);
    Route::post('/albums/{album}/images', [AlbumController::class, 'addImage']);
    Route::delete('/albums/{album}/images', [AlbumController::class, 'removeImages']);

    // Binomials
    Route::post('/images/{image}/binomials', [BinomialEvaluationController::class, 'store']);

    // Reports
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/{report}', [ReportController::class, 'show']);
    Route::patch('/reports/{report}', [ReportController::class, 'update']);

    //suggestions
    Route::post('/images/{image}/suggestions', [ImageSuggestionController::class, 'store']);
    Route::put('/image-suggestions/{imageSuggestion}', [ImageSuggestionController::class, 'update']);
    Route::delete('/image-suggestions/{imageSuggestion}', [ImageSuggestionController::class, 'destroy']);
    Route::post('/image-suggestions/{imageSuggestion}/accept', [ImageSuggestionController::class, 'accept']);
    Route::post('/image-suggestions/{imageSuggestion}/reject', [ImageSuggestionController::class, 'reject']);
    Route::post('/vrac-works/{work}/suggestions', [WorkSuggestionController::class, 'store']);
    Route::put('/work-suggestions/{workSuggestion}', [WorkSuggestionController::class, 'update']);
    Route::delete('/work-suggestions/{workSuggestion}', [WorkSuggestionController::class, 'destroy']);
    Route::post('/work-suggestions/{workSuggestion}/accept', [WorkSuggestionController::class, 'accept']);
    Route::post('/work-suggestions/{workSuggestion}/reject', [WorkSuggestionController::class, 'reject']);
    Route::post('collectives/{collective}/join-requests', [CollectiveJoinRequestController::class, 'store']);
    Route::get('collectives/{collective}/join-requests', [CollectiveJoinRequestController::class, 'index']);
    Route::put('collectives/{collective}/join-requests/{user}', [CollectiveJoinRequestController::class, 'update']);
    Route::delete('collectives/{collective}/join-requests/{user}', [CollectiveJoinRequestController::class, 'destroy']);

    // VRACore vocabularies (manual Arquigrafia entries)
    Route::apiResource('vrac-agent-roles', VRACAgentRoleController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('vrac-materials', VRACMaterialController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('vrac-style-periods', VRACStylePeriodController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('vrac-techniques', VRACTechniqueController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('vrac-work-types', VRACWorkTypeController::class)->only(['store', 'update', 'destroy']);
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
Route::apiResource('vrac-agent-roles', VRACAgentRoleController::class)->only(['index', 'show']);
Route::apiResource('vrac-contributor-names', VRACContributorNameController::class);
Route::apiResource('vrac-cultural-contexts', VRACCulturalContextController::class);
Route::apiResource('vrac-dates', VRACDateController::class);
Route::apiResource('vrac-descriptions', VRACDescriptionController::class);
Route::apiResource('vrac-inscriptions', VRACInscriptionController::class);
Route::apiResource('vrac-location-names', VRACLocationNameController::class);
Route::apiResource('vrac-locations', VRACLocationController::class);
Route::apiResource('vrac-materials', VRACMaterialController::class)->only(['index', 'show']);
Route::apiResource('vrac-measurements', VRACMeasurementController::class);
Route::apiResource('vrac-rights', VRACRightController::class);
Route::apiResource('vrac-sources', VRACSourceController::class);
Route::apiResource('vrac-state-editions', VRACStateEditionController::class);
Route::apiResource('vrac-style-periods', VRACStylePeriodController::class)->only(['index', 'show']);
Route::apiResource('vrac-subjects', VRACSubjectController::class);
Route::apiResource('vrac-techniques', VRACTechniqueController::class)->only(['index', 'show']);
Route::apiResource('vrac-text-refs', VRACTextRefController::class);
Route::apiResource('vrac-titles', VRACTitleController::class);
Route::apiResource('vrac-work-types', VRACWorkTypeController::class)->only(['index', 'show']);
Route::apiResource('vrac-works', VRACWorkController::class)->only(['index', 'show']);
