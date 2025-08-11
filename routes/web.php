<?php

use App\Http\Controllers\IIIFManifestController;
use Illuminate\Support\Facades\Route;

Route::get('/iiif/{id}/manifest', [IIIFManifestController::class, 'getManifest'])->name('iiif.manifest');

Route::get('/', function () {
    return view('welcome');
});
