<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACLocationName;
use Illuminate\Http\Request;

class VRACLocationNameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locationNames = VRACLocationName::all();

        return response()->json([
            'location_names' => $locationNames,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $locationName = new VRACLocationName();

        $locationName->label = $request->input('label');
        $locationName->type = $request->input('type');
        $locationName->vocab = $request->input('vocab');
        $locationName->ref_id = $request->input('ref_id');
        $locationName->save();

        return response()->json([
            'location_name' => $locationName,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $locationName = VRACLocationName::with('locations')->find($id);

        return response()->json([
            'location_name' => $locationName,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $locationName = VRACLocationName::find($id);

        $locationName->label = $request->input('label');
        $locationName->type = $request->input('type');
        $locationName->vocab = $request->input('vocab');
        $locationName->ref_id = $request->input('ref_id');
        $locationName->save();

        return response()->json([
            'location_name' => $locationName,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $locationName = VRACLocationName::find($id);
        
        $locationName->delete();

        return response()->json([
            'location_name' => $locationName,
        ]);
    }
}
