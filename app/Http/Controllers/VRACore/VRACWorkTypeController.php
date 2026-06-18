<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACWorkType;
use Illuminate\Http\Request;

/**
 * @group VRACore - Tipos de Obra
 * @unauthenticated
 */
class VRACWorkTypeController extends Controller
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
            return response()->json(['data' => VRACWorkType::all()]);
        }

        return VRACWorkType::paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $workType = new VRACWorkType;

        $workType->label = $request->input('label');
        $workType->vocab = 'Arquigrafia';
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
