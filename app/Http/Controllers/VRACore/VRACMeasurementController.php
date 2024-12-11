<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACMeasurement;
use Illuminate\Http\Request;

class VRACMeasurementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $measurements = VRACMeasurement::all();

        return response()->json([
            'measurements' => $measurements,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $measurement = new VRACMeasurement();

        $measurement->value = $request->input('value');
        $measurement->type = $request->input('type');
        $measurement->unit = $request->input('unit');
        $measurement->extent = $request->input('extent');
        $measurement->save();

        return response()->json([
            'measurement' => $measurement,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $measurement = VRACMeasurement::find($id);

        return response()->json([
            'measurement' => $measurement,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $measurement = VRACMeasurement::find($id);

        $measurement->value = $request->input('value');
        $measurement->type = $request->input('type');
        $measurement->unit = $request->input('unit');
        $measurement->extent = $request->input('extent');
        $measurement->save();

        return response()->json([
            'measurement' => $measurement,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $measurement = VRACMeasurement::find($id);
        
        $measurement->delete();
        
        return response()->json([
            'measurement' => $measurement,
        ]);
    }
}
