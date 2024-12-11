<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACAgentRole;
use Illuminate\Http\Request;

class VRACAgentRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = VRACAgentRole::all();

        return response()->json([
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $role = new VRACAgentRole();

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
