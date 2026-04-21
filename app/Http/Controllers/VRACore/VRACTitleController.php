<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACTitle;
use Illuminate\Http\Request;

/**
 * @group VRACore - Títulos
 * @unauthenticated
 */
class VRACTitleController extends Controller
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
            return response()->json(['data' => VRACTitle::all()]);
        }

        return VRACTitle::paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $title = new VRACTitle;

        $title->label = $request->input('label');
        $title->type = $request->input('type');
        $title->pref = $request->input('pref');
        $title->source = $request->input('source');
        $title->lang = $request->input('lang');
        $title->href = $request->input('href');
        $title->save();

        return response()->json([
            'title' => $title,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $title = VRACTitle::find($id);

        return response()->json([
            'title' => $title,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $title = VRACTitle::find($id);

        $title->label = $request->input('label');
        $title->type = $request->input('type');
        $title->pref = $request->input('pref');
        $title->source = $request->input('source');
        $title->lang = $request->input('lang');
        $title->href = $request->input('href');
        $title->save();

        return response()->json([
            'title' => $title,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $title = VRACTitle::find($id);

        $title->delete();

        return response()->json([
            'title' => $title,
        ]);
    }
}
