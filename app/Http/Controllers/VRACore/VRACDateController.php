<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\VRACore\VRACDate;
use Illuminate\Http\Request;

/**
 * @group VRACore - Datas
 * @unauthenticated
 */
class VRACDateController extends Controller
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
            return response()->json(['data' => VRACDate::all()]);
        }

        return VRACDate::paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $date = new VRACDate;

        $date->type = $request->input('type');
        $date->earliest_date = $request->input('earliest_date');
        $date->circa_earliest_date = $request->boolean('circa_earliest_date');
        $date->latest_date = $request->input('latest_date');
        $date->circa_latest_date = $request->boolean('circa_latest_date');
        $date->source = $request->input('source');
        $date->href = $request->input('href');
        $date->save();

        return response()->json([
            'date' => $date,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $date = VRACDate::find($id);

        return response()->json([
            'date' => $date,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $date = VRACDate::find($id);

        $date->type = $request->input('type');
        $date->earliest_date = $request->input('earliest_date');
        $date->circa_earliest_date = $request->boolean('circa_earliest_date');
        $date->latest_date = $request->input('latest_date');
        $date->circa_latest_date = $request->boolean('circa_latest_date');
        $date->source = $request->input('source');
        $date->href = $request->input('href');
        $date->save();

        return response()->json([
            'date' => $date,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $date = VRACDate::find($id);

        $date->delete();

        return response()->json([
            'date' => $date,
        ]);
    }
}
