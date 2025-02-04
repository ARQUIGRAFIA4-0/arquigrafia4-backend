<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACStateEdition;
use Illuminate\Http\Request;

class VRACStateEditionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $editions = VRACStateEdition::all();

        return response()->json([
            'editions' => $editions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $edition = new VRACStateEdition();

        $edition->name = $request->input('name');
        $edition->description = $request->input('description');
        $edition->type = $request->input('type');
        $edition->num = $request->input('num');
        $edition->count = $request->input('count');
        $edition->source = $request->input('source');
        $edition->save();

        return response()->json([
            'edition' => $edition,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $edition = VRACStateEdition::find($id);

        return response()->json([
            'edition' => $edition,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $edition = VRACStateEdition::find($id);

        $edition->name = $request->input('name');
        $edition->description = $request->input('description');
        $edition->type = $request->input('type');
        $edition->num = $request->input('num');
        $edition->count = $request->input('count');
        $edition->source = $request->input('source');
        $edition->save();

        return response()->json([
            'edition' => $edition,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $edition = VRACStateEdition::find($id);

        $edition->delete();

        return response()->json([
            'edition' => $edition,
        ]);
    }
}
