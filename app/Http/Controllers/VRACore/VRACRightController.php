<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACRight;
use Illuminate\Http\Request;

class VRACRightController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rights = VRACRight::all();

        return response()->json([
            'rights' => $rights,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $right = new VRACRight();

        $right->text = $request->input('text');
        $right->type = $request->input('type');
        $right->href = $request->input('href');
        $right->rights_holder = $request->input('rights_holder');
        $right->save();

        return response()->json([
            'right' => $right,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $right = VRACRight::find($id);

        return response()->json([
            'right' => $right,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $right = VRACRight::find($id);

        $right->text = $request->input('text');
        $right->type = $request->input('type');
        $right->href = $request->input('href');
        $right->rights_holder = $request->input('rights_holder');
        $right->save();

        return response()->json([
            'right' => $right,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $right = VRACRight::find($id);

        $right->delete();

        return response()->json([
            'right' => $right,
        ]);
    }
}
