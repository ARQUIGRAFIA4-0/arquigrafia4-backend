<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACSource;
use Illuminate\Http\Request;

class VRACSourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sources = VRACSource::all();

        return response()->json([
            'sources' => $sources,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $source = new VRACSource();

        $source->name = $request->input('name');
        $source->name_type = $request->input('name_type');
        $source->ref_id = $request->input('ref_id');
        $source->ref_type = $request->input('ref_type');
        $source->save();

        return response()->json([
            'source' => $source,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $source = VRACSource::find($id);

        return response()->json([
            'source' => $source,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $source = VRACSource::find($id);

        $source->name = $request->input('name');
        $source->name_type = $request->input('name_type');
        $source->ref_id = $request->input('ref_id');
        $source->ref_type = $request->input('ref_type');
        $source->save();

        return response()->json([
            'source' => $source,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $source = VRACSource::find($id);

        $source->delete();

        return response()->json([
            'source' => $source,
        ]);
    }
}
