<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACStylePeriod;
use Illuminate\Http\Request;

class VRACStylePeriodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $periods = VRACStylePeriod::all();

        return response()->json([
            'periods' => $periods,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $period = new VRACStylePeriod();

        $period->label = $request->input('label');
        $period->ref_id = $request->input('ref_id');
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
        $period->ref_id = $request->input('ref_id');
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
