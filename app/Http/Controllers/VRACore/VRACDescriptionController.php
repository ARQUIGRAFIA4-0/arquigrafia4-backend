<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACDescription;
use Illuminate\Http\Request;

class VRACDescriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $descriptions = VRACDescription::all();

        return response()->json([
            'descriptions' => $descriptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $description = new VRACDescription();

        $description->text = $request->input('text');
        $description->source = $request->input('source');
        $description->save();

        return response()->json([
            'description' => $description,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $description = VRACDescription::find($id);

        return response()->json([
            'description' => $description,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $description = VRACDescription::find($id);

        $description->text = $request->input('text');
        $description->source = $request->input('source');
        $description->save();

        return response()->json([
            'description' => $description,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $description = VRACDescription::find($id);
        
        $description->delete();

        return response()->json([
            'description' => $description,
        ]);
    }
}
