<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACInscription;
use Illuminate\Http\Request;

class VRACInscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inscriptions = VRACInscription::all();

        return response()->json([
            'inscriptions' => $inscriptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $inscription = new VRACInscription();

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
