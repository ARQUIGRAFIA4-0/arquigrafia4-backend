<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACContributorName;
use Illuminate\Http\Request;

/**
 * @group VRACore - Nomes de Contribuidores
 * @unauthenticated
 */
class VRACContributorNameController extends Controller
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
            return response()->json(['data' => VRACContributorName::all()]);
        }

        return VRACContributorName::paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $cName = new VRACContributorName;

        $cName->name = $request->input('name');
        $cName->type = $request->input('type', 'individual');
        $cName->vocab = $request->input('vocab');
        $cName->ref_id = $request->input('ref_id');
        $cName->save();

        return response()->json([
            'name' => $cName,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cName = VRACContributorName::with('agents')->find($id);

        return response()->json([
            'name' => $cName,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $cName = VRACContributorName::find($id);

        $cName->name = $request->input('name');
        $cName->type = $request->input('type', 'individual');
        $cName->vocab = $request->input('vocab');
        $cName->ref_id = $request->input('ref_id');
        $cName->save();

        return response()->json([
            'name' => $cName,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cName = VRACContributorName::find($id);

        $cName->delete();

        return response()->json([
            'name' => $cName,
        ]);
    }

    public function getByUserId(string $userId)
    {
        $cName = VRACContributorName::where('user_id', $userId)->first();

        return response()->json([
            'name' => $cName,
        ]);
    }
}
