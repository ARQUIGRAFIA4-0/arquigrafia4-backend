<?php

namespace App\Http\Controllers\VRACore;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\VRACore\VRACWork;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * @group VRACore - Obras
 */
class VRACWorkController extends Controller
{
    private const EAGER = [
        'location',
        'titles',
        'agents.contributorName',
        'agents.role',
        'dates',
        'materials',
        'techniques',
        'stylePeriods',
        'culturalContexts',
        'workTypes',
        'subjects',
    ];

    /**
     * Display a listing of works.
     *
     * @unauthenticated
     *
     * @queryParam per_page integer Items per page. Use -1 to fetch all records without pagination. Default 15.
     * @queryParam bbox string Optional bounding box filter `west,south,east,north` (lng/lat). Only works whose location falls inside the box are returned.
     */
    public function index(Request $request)
    {
        $query = VRACWork::query()->with(self::EAGER);

        if ($bbox = $request->input('bbox')) {
            $this->applyBboxFilter($query, $bbox);
        }

        $perPage = $request->integer('per_page', 15);

        if ($perPage === -1) {
            return response()->json(['data' => $query->get()]);
        }

        return $query->paginate($perPage);
    }

    /**
     * Store a newly created work.
     *
     * @bodyParam location_id string UUID of an existing location. Required if latitude/longitude are not provided.
     * @bodyParam latitude numeric Latitude for a new location. Required if location_id is not provided.
     * @bodyParam longitude numeric Longitude for a new location. Required if location_id is not provided.
     * @bodyParam location_label string Human-readable label/address for the new location.
     * @bodyParam titles string[] UUIDs of existing VRACTitle records. At least one preferred title is required.
     * @bodyParam agents string[] UUIDs of existing VRACAgent records.
     * @bodyParam dates string[] UUIDs of existing VRACDate records.
     * @bodyParam materials string[] UUIDs of existing VRACMaterial records.
     * @bodyParam techniques string[] UUIDs of existing VRACTechnique records.
     * @bodyParam style_periods string[] UUIDs of existing VRACStylePeriod records.
     */
    public function store(Request $request)
    {
        $request->validate([
            'location_id' => 'nullable|uuid|exists:locations,id',
            'latitude' => 'nullable|decimal:1,8|required_without:location_id',
            'longitude' => 'nullable|decimal:1,8|required_without:location_id',
            'location_label' => 'nullable|string|max:255',
            'titles' => 'required|array|min:1',
            'titles.*' => 'uuid|exists:vrac_titles,id',
            'agents' => 'nullable|array',
            'agents.*' => 'uuid|exists:vrac_agents,id',
            'dates' => 'nullable|array',
            'dates.*' => 'uuid|exists:vrac_dates,id',
            'materials' => 'nullable|array',
            'materials.*' => 'uuid|exists:vrac_materials,id',
            'techniques' => 'nullable|array',
            'techniques.*' => 'uuid|exists:vrac_techniques,id',
            'style_periods' => 'nullable|array',
            'style_periods.*' => 'uuid|exists:vrac_style_periods,id',
            'cultural_contexts' => 'nullable|array',
            'cultural_contexts.*' => 'uuid|exists:vrac_cultural_contexts,id',
            'work_types' => 'nullable|array',
            'work_types.*' => 'uuid|exists:vrac_work_types,id',
            'subjects' => 'nullable|array',
            'subjects.*' => 'uuid|exists:vrac_subjects,id',
        ]);

        $work = new VRACWork();
        $work->location_id = $this->resolveLocationId($request);
        $work->save();

        $work->titles()->sync($request->input('titles'));
        $work->agents()->sync($request->input('agents', []));
        $work->dates()->sync($request->input('dates', []));
        $work->materials()->sync($request->input('materials', []));
        $work->techniques()->sync($request->input('techniques', []));
        $work->stylePeriods()->sync($request->input('style_periods', []));
        $work->culturalContexts()->sync($request->input('cultural_contexts', []));
        $work->workTypes()->sync($request->input('work_types', []));
        $work->subjects()->sync($request->input('subjects', []));

        $work->load(self::EAGER);

        return response()->json(['data' => $work], 201);
    }

    /**
     * Display the specified work.
     *
     * @unauthenticated
     */
    public function show(string $id)
    {
        $work = VRACWork::with(self::EAGER)->findOrFail($id);

        return response()->json(['data' => $work]);
    }

    /**
     * Update the specified work.
     */
    public function update(Request $request, string $id)
    {
        $work = VRACWork::findOrFail($id);

        $request->validate([
            'location_id' => 'nullable|uuid|exists:locations,id',
            'titles' => 'sometimes|array|min:1',
            'titles.*' => 'uuid|exists:vrac_titles,id',
            'agents' => 'sometimes|array',
            'agents.*' => 'uuid|exists:vrac_agents,id',
            'dates' => 'sometimes|array',
            'dates.*' => 'uuid|exists:vrac_dates,id',
            'materials' => 'sometimes|array',
            'materials.*' => 'uuid|exists:vrac_materials,id',
            'techniques' => 'sometimes|array',
            'techniques.*' => 'uuid|exists:vrac_techniques,id',
            'style_periods' => 'sometimes|array',
            'style_periods.*' => 'uuid|exists:vrac_style_periods,id',
            'cultural_contexts' => 'sometimes|array',
            'cultural_contexts.*' => 'uuid|exists:vrac_cultural_contexts,id',
            'work_types' => 'sometimes|array',
            'work_types.*' => 'uuid|exists:vrac_work_types,id',
            'subjects' => 'sometimes|array',
            'subjects.*' => 'uuid|exists:vrac_subjects,id',
        ]);

        if ($request->has('location_id')) {
            $work->location_id = $request->input('location_id');
            $work->save();
        }

        foreach (
            [
                'titles' => 'titles',
                'agents' => 'agents',
                'dates' => 'dates',
                'materials' => 'materials',
                'techniques' => 'techniques',
                'style_periods' => 'stylePeriods',
                'cultural_contexts' => 'culturalContexts',
                'work_types' => 'workTypes',
                'subjects' => 'subjects',
            ] as $input => $relation
        ) {
            if ($request->has($input)) {
                $work->{$relation}()->sync($request->input($input, []));
            }
        }

        $work->load(self::EAGER);

        return response()->json(['data' => $work]);
    }

    /**
     * Remove the specified work.
     */
    public function destroy(string $id)
    {
        $work = VRACWork::findOrFail($id);
        $work->delete();

        return response()->json(['data' => $work]);
    }

    private function resolveLocationId(Request $request): ?string
    {
        if ($request->filled('location_id')) {
            return $request->input('location_id');
        }

        $location = new Location();
        $location->latitude = $request->input('latitude');
        $location->longitude = $request->input('longitude');
        $location->label = $request->input('location_label');
        $location->save();

        return $location->id;
    }

    private function applyBboxFilter(Builder $query, string $bbox): void
    {
        $parts = array_map('trim', explode(',', $bbox));
        if (count($parts) !== 4) {
            return;
        }

        [$west, $south, $east, $north] = array_map('floatval', $parts);

        $query->whereHas('location', function (Builder $q) use ($west, $south, $east, $north) {
            $q->whereBetween('longitude', [$west, $east])
                ->whereBetween('latitude', [$south, $north]);
        });
    }
}
