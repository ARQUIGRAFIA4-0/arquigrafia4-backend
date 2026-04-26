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
     *
     * @queryParam per_page integer The number of items per page. Use -1 to fetch all records without pagination. Default is 15.
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);

        if ($perPage === -1) {
            return response()->json(['data' => VRACSubject::all()]);
        }

        return VRACSubject::paginate($perPage);
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
