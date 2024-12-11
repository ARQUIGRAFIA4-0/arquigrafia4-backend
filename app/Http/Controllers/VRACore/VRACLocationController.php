<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACLocation;
use Illuminate\Http\Request;

class VRACLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locations = VRACLocation::all();

        return response()->json([
            'locations' => $locations,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $location = new VRACLocation();

        $location->location_name_id = $request->input('location_name_id');
        $location->type = $request->input('type');
        $location->ref_id = $request->input('ref_id');
        $location->save();

        return response()->json([
            'location' => $location,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $location = VRACLocation::with('name')->find($id);

        return response()->json([
            'location' => $location,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $location = VRACLocation::find($id);

        $location->location_name_id = $request->input('location_name_id');
        $location->type = $request->input('type');
        $location->ref_id = $request->input('ref_id');
        $location->save();

        return response()->json([
            'location' => $location,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $location = VRACLocation::find($id);
        
        $location->delete();
        
        return response()->json([
            'location' => $location,
        ]);
    }
}
