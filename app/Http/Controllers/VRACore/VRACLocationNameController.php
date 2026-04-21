<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACLocationName;
use Illuminate\Http\Request;

/**
 * @group VRACore - Nomes de Localizações
 * @unauthenticated
 */
class VRACLocationNameController extends Controller
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
            return response()->json(['data' => VRACLocationName::all()]);
        }

        return VRACLocationName::paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $locationName = new VRACLocationName;

        $locationName->label = $request->input('label');
        $locationName->type = $request->input('type');
        $locationName->vocab = $request->input('vocab');
        $locationName->ref_id = $request->input('ref_id');
        $locationName->save();

        return response()->json([
            'location_name' => $locationName,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $locationName = VRACLocationName::with('locations')->find($id);

        return response()->json([
            'location_name' => $locationName,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $locationName = VRACLocationName::find($id);

        $locationName->label = $request->input('label');
        $locationName->type = $request->input('type');
        $locationName->vocab = $request->input('vocab');
        $locationName->ref_id = $request->input('ref_id');
        $locationName->save();

        return response()->json([
            'location_name' => $locationName,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $locationName = VRACLocationName::find($id);

        $locationName->delete();

        return response()->json([
            'location_name' => $locationName,
        ]);
    }
}
