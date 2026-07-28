<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACCulturalContext;
use Illuminate\Http\Request;

/**
 * @group VRACore - Contextos Culturais
 * @unauthenticated
 */
class VRACCulturalContextController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @queryParam per_page integer Items per page. Use -1 for all records. Default: 15.
     * @queryParam search string Filter by label (case and accent insensitive). Example: brasileiro
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $search  = $request->string('search')->trim();

        $query = VRACCulturalContext::when($search, fn($q) => $q->where('label', 'like', "%{$search}%"))
            ->orderBy('label');

        if ($perPage === -1) {
            return response()->json(['data' => $query->get()]);
        }

        return $query->paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @authenticated
     */
    public function store(Request $request)
    {
        $context = new VRACCulturalContext;

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
