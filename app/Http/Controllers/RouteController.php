<?php

namespace App\Http\Controllers;

use App\Services\OsrmService;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function route(Request $request, OsrmService $osrm)
    {
        $validated = $request->validate([
            'coordinates' => ['required', 'array', 'min:2'],
            'coordinates.*' => ['required', 'array', 'size:2'],
            'coordinates.*.0' => ['required', 'numeric', 'between:-180,180'],
            'coordinates.*.1' => ['required', 'numeric', 'between:-90,90'],
        ]);

        $result = $osrm->route(
            $validated['coordinates']
        );

        return response()->json($result);
    }
}
