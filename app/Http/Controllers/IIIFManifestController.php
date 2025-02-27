<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Models\VRACore\VRACImage;

class IIIFManifestController extends Controller
{
    public function getManifest($id)
    {
        $image = VRACImage::with(['title'])->findOrFail($id);

        $manifest = [
            '@context' => 'http://iiif.io/api/presentation/3/context.json',
            'id' => route('iiif.manifest', ['id' => $id]),
            'type' => 'Manifest',
            'label' => ['none' => [$image->title->label]],
        ];

        return Response::json($manifest, 200, ['Content-Type' => 'application/ld+json']);
    }
}
