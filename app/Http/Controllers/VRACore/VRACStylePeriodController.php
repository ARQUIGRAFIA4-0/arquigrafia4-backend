<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACStylePeriod;
use Illuminate\Http\Request;

/**
 * @group VRACore - Estilos/Períodos
 * @unauthenticated
 */
class VRACStylePeriodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @queryParam per_page integer Items per page. Use -1 for all records. Default: 15.
     * @queryParam search string Filter by label (case and accent insensitive). Example: moderno
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $search  = $request->string('search')->trim();

        $query = VRACStylePeriod::when($search, fn($q) => $q->where('label', 'like', "%{$search}%"))
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
        $period = new VRACStylePeriod;

        $period->label = $request->input('label');
        $period->vocab = 'Arquigrafia';
        $period->save();

        return response()->json([
            'period' => $period,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $period = VRACStylePeriod::find($id);

        return response()->json([
            'period' => $period,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $period = VRACStylePeriod::find($id);

        $period->label = $request->input('label');
        $period->save();

        return response()->json([
            'period' => $period,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $period = VRACStylePeriod::find($id);

        $period->delete();

        return response()->json([
            'period' => $period,
        ]);
    }
}
