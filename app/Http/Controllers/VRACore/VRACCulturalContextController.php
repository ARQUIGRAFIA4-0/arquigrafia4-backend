<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACCulturalContext;
use Illuminate\Http\Request;

class VRACCulturalContextController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contexts = VRACCulturalContext::all();

        return response()->json([
            'contexts' => $contexts,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $context = new VRACCulturalContext();

        $context->label = $request->input('label');
        $context->vocab = $request->input('vocab');
        $context->ref_id = $request->input('ref_id');
        $context->save();

        return response()->json([
            'context' => $context,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $context = VRACCulturalContext::with('agents')->find($id);

        return response()->json([
            'context' => $context,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $context = VRACCulturalContext::find($id);

        $context->label = $request->input('label');
        $context->vocab = $request->input('vocab');
        $context->ref_id = $request->input('ref_id');
        $context->save();

        return response()->json([
            'context' => $context,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $context = VRACCulturalContext::find($id);

        $context->delete();

        return response()->json([
            'context' => $context,
        ]);
    }
}
