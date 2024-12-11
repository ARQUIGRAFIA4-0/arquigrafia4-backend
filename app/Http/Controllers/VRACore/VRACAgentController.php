<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACAgent;
use Illuminate\Http\Request;

class VRACAgentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $agents = VRACAgent::all();

        return response()->json([
            'agents' => $agents,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $agent = new VRACAgent();

        $agent->contributor_name_id = $request->input('contributor_name_id');
        $agent->role_id = $request->input('role_id');
        $agent->attribution = $request->input('attribution');
        $agent->culture_id = $request->input('culture_id');
        $agent->save();

        return response()->json([
            'agent' => $agent,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $agent = VRACAgent::with('contributorName', 'role')->find($id);
        
        return response()->json([
            'agent' => $agent,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $agent = VRACAgent::find($id);

        $agent->contributor_name_id = $request->input('contributor_name_id');
        $agent->role_id = $request->input('role_id');
        $agent->attribution = $request->input('attribution');
        $agent->culture_id = $request->input('culture_id');
        $agent->save();

        return response()->json([
            'agent' => $agent,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $agent = VRACAgent::find($id);
        
        $agent->delete();

        return response()->json([
            'agent' => $agent,
        ]);
    }
}
