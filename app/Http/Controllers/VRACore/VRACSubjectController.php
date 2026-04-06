<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACSubject;
use Illuminate\Http\Request;

/**
 * @group VRACore - Assuntos
 * @unauthenticated
 */
class VRACSubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return VRACSubject::paginate();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $subject = new VRACSubject;

        $subject->term = $request->input('term');
        $subject->type = $request->input('type');
        $subject->vocab = $request->input('vocab');
        $subject->ref_id = $request->input('ref_id');
        $subject->source = $request->input('source');
        $subject->save();

        return response()->json([
            'data' => $subject,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $subject = VRACSubject::find($id);

        return response()->json([
            'data' => $subject,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $subject = VRACSubject::find($id);

        $subject->term = $request->input('term');
        $subject->type = $request->input('type');
        $subject->vocab = $request->input('vocab');
        $subject->ref_id = $request->input('ref_id');
        $subject->source = $request->input('source');
        $subject->save();

        return response()->json([
            'data' => $subject,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $subject = VRACSubject::find($id);

        $subject->delete();

        return response()->json([
            'data' => $subject,
        ]);
    }
}
