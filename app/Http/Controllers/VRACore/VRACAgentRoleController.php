<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACAgentRole;
use Illuminate\Http\Request;

/**
 * @group VRACore - Papéis de Agentes
 * @unauthenticated
 */
class VRACAgentRoleController extends Controller
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
            return response()->json(['data' => VRACAgentRole::all()]);
        }

        return VRACAgentRole::paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $role = new VRACAgentRole;

        $role->label = $request->input('label');
        $role->vocab = $request->input('vocab');
        $role->ref_id = $request->input('ref_id');
        $role->save();

        return response()->json([
            'role' => $role,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = VRACAgentRole::with('agents')->find($id);

        return response()->json([
            'role' => $role,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $role = VRACAgentRole::find($id);

        $role->label = $request->input('label');
        $role->vocab = $request->input('vocab');
        $role->ref_id = $request->input('ref_id');
        $role->save();

        return response()->json([
            'role' => $role,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = VRACAgentRole::find($id);

        $role->delete();

        return response()->json([
            'role' => $role,
        ]);
    }
}
