<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACTextRef;
use Illuminate\Http\Request;

class VRACTextRefController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $refs = VRACTextRef::all();

        return response()->json([
            'refs' => $refs,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $ref = new VRACTextRef();

        $ref->name = $request->input('name');
        $ref->name_type = $request->input('name_type');
        $ref->ref_id = $request->input('ref_id');
        $ref->ref_type = $request->input('ref_type');
        $ref->save();

        return response()->json([
            'ref' => $ref,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ref = VRACTextRef::find($id);

        return response()->json([
            'ref' => $ref,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $ref = VRACTextRef::find($id);

        $ref->name = $request->input('name');
        $ref->name_type = $request->input('name_type');
        $ref->ref_id = $request->input('ref_id');
        $ref->ref_type = $request->input('ref_type');
        $ref->save();

        return response()->json([
            'ref' => $ref,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ref = VRACTextRef::find($id);

        $ref->delete();

        return response()->json([
            'ref' => $ref,
        ]);
    }
}
