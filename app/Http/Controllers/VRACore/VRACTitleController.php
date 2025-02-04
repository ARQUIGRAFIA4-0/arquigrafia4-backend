<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACTitle;
use Illuminate\Http\Request;

class VRACTitleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $titles = VRACTitle::all();

        return response()->json([
            'titles' => $titles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $title = new VRACTitle();

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
