<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACDate;
use Illuminate\Http\Request;

class VRACDateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dates = VRACDate::all();

        return response()->json([
            'dates' => $dates,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $date = new VRACDate();

        $date->type = $request->input('type');
        $date->earliest_date = $request->input('earliest_date');
        $date->circa_earliest_date = $request->boolean('circa_earliest_date');
        $date->latest_date = $request->input('latest_date');
        $date->circa_latest_date = $request->boolean('circa_latest_date');
        $date->source = $request->input('source');
        $date->href = $request->input('href');
        $date->save();

        return response()->json([
            'date' => $date,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $date = VRACDate::find($id);

        return response()->json([
            'date' => $date,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $date = VRACDate::find($id);

        $date->type = $request->input('type');
        $date->earliest_date = $request->input('earliest_date');
        $date->circa_earliest_date = $request->boolean('circa_earliest_date');
        $date->latest_date = $request->input('latest_date');
        $date->circa_latest_date = $request->boolean('circa_latest_date');
        $date->source = $request->input('source');
        $date->href = $request->input('href');
        $date->save();

        return response()->json([
            'date' => $date,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $date = VRACDate::find($id);
        
        $date->delete();

        return response()->json([
            'date' => $date,
        ]);
    }
}
