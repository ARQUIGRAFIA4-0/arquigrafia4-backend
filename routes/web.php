<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IIIFManifestController;
use App\Http\Controllers\ImageController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/images/download/{id}', [ImageController::class, 'downloadFull']);

/// IIIF
Route::get('/iiif/{id}/manifest', [IIIFManifestController::class, 'getManifest'])->name('iiif.manifest');
