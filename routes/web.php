<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IIIFManifestController;

Route::get('/', function () {
    return view('welcome');
});

/// IIIF
Route::get('/iiif/{id}/manifest', [IIIFManifestController::class, 'getManifest'])->name('iiif.manifest');
