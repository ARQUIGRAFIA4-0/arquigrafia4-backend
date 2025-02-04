<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACWorkType;
use Illuminate\Http\Request;

class VRACWorkTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $workTypes = VRACWorkType::all();

        return response()->json([
            'work_types' => $workTypes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $workType = new VRACWorkType();

        $workType->label = $request->input('label');
        $workType->vocab = $request->input('vocab');
        $workType->ref_id = $request->input('ref_id');
        $workType->save();

        return response()->json([
            'work_type' => $workType,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $workType = VRACWorkType::find($id);

        return response()->json([
            'work_type' => $workType,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $workType = VRACWorkType::find($id);

        $workType->label = $request->input('label');
        $workType->vocab = $request->input('vocab');
        $workType->ref_id = $request->input('ref_id');
        $workType->save();

        return response()->json([
            'work_type' => $workType,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $workType = VRACWorkType::find($id);

        $workType->delete();

        return response()->json([
            'work_type' => $workType,
        ]);
    }
}
