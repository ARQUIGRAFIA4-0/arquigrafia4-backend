<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACRight;
use Illuminate\Http\Request;

/**
 * @group VRACore - Direitos
 * @unauthenticated
 */
class VRACRightController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @queryParam per_page integer The number of items per page. Use -1 to fetch all records without pagination. Default is 15.
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);

        if ($perPage === -1) {
            return response()->json(['data' => VRACRight::all()]);
        }

        return VRACRight::paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $right = new VRACRight;

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
