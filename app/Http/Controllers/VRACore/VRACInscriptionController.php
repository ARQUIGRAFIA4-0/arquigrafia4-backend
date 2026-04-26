<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACInscription;
use Illuminate\Http\Request;

/**
 * @group VRACore - Inscrições
 * @unauthenticated
 */
class VRACInscriptionController extends Controller
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
            return response()->json(['data' => VRACInscription::all()]);
        }

        return VRACInscription::paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $inscription = new VRACInscription;

        $inscription->label = $request->input('label');
        $inscription->type = $request->input('type');
        $inscription->position = $request->input('position');
        $inscription->author = $request->input('author');
        $inscription->save();

        return response()->json([
            'inscription' => $inscription,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $inscription = VRACInscription::find($id);

        return response()->json([
            'inscription' => $inscription,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $inscription = VRACInscription::find($id);

        $inscription->label = $request->input('label');
        $inscription->type = $request->input('type');
        $inscription->position = $request->input('position');
        $inscription->author = $request->input('author');
        $inscription->save();

        return response()->json([
            'inscription' => $inscription,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $inscription = VRACInscription::find($id);

        $inscription->delete();

        return response()->json([
            'inscription' => $inscription,
        ]);
    }
}
