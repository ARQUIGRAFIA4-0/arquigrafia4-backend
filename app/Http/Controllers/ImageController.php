<?php

namespace App\Http\Controllers;

use App\Actions\Images\ApplyImageChangesAction;
use App\Http\Requests\Image\SearchImageRequest;
use App\Http\Requests\Image\StoreImageRequest;
use App\Http\Requests\Image\UpdateImageRequest;
use App\Http\Resources\ImageResource;
use App\Jobs\TileImage;
use App\Models\Location;
use App\Models\VRACore\VRACAgent;
use App\Models\VRACore\VRACAgentRole;
use App\Models\VRACore\VRACDate;
use App\Models\VRACore\VRACDescription;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACRight;
use App\Models\VRACore\VRACTitle;
use App\Services\ImageSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Jcupitt\Vips\Image as VipsImage;

/**
 * @group Imagens
 *
 * Busca, visualização e gerenciamento de imagens de arquitetura.
 */
class ImageController extends Controller
{
    private int $pageSize = 50;

    /**
     * Listar / buscar imagens
     *
     * Retorna imagens paginadas com suporte a filtros de texto, data da imagem, data da obra,
     * licença, assunto, contribuidor, binômios e coletivo. Todos os filtros são combinados com AND.
     *
     * @group Imagens
     *
     * @unauthenticated
     */
    public function index(SearchImageRequest $request, ImageSearchService $searchService)
    {
        $query = VRACImage::query();

        $searchService->apply($query, $request->validated());

        $images = $query->with([
            'subjects:id,term',
            'dates:id,type,earliest_date,latest_date,circa_earliest_date,circa_latest_date',
            'titles:id,label,type',
            'rights:id,type,href,text,rights_holder',
        ])->paginate($request->integer('per_page', $this->pageSize));

        return ImageResource::collection($images);
    }

    public function store(StoreImageRequest $request)
    {
        $image = new VRACImage;
        $image->user_id = $request->input('user_id');
        $image->collective_id = $request->input('collective_id');
        $image->save();

        // load original into vips from uploaded buffer and capture original dimensions
        $uploaded = VipsImage::newFromBuffer($request->file('image')->getContent(), '', ['access' => 'sequential']);
        $origWidth = $uploaded->width ?? null;
        $origHeight = $uploaded->height ?? null;
        $converted = $uploaded->writeToBuffer('.jpg');
        Storage::disk('public')->put($image->path('original'), $converted);

        $title = new VRACTitle;
        $title->label = $request->input('title');
        $title->type = 'other';
        $title->save();
        $image->titles()->sync($title->id);

        $photographerRole = VRACAgentRole::getPhotographer();
        $agentPhotographer = VRACAgent::firstOrCreate([
            'role_id' => $photographerRole->id,
            'contributor_name_id' => $request->input('photographer'),
        ]);
        $agentPhotographer->load('contributorName');
        $image->agents()->sync($agentPhotographer->id);

        $right = new VRACRight;
        $right->text = Str::upper($request->input('license'));
        $right->type = 'copyrighted';
        $right->href = 'https://creativecommons.org/licenses/'.Str::lower($request->input('license')).'/4.0';
        $right->rights_holder = $agentPhotographer->contributorName->name;
        $right->save();
        $image->rights()->sync($right->id);

        $image->subjects()->sync($request->input('subjects'));

        if ($request->filled('works')) {
            $image->works()->sync($request->input('works'));
        }

        if ($request->filled('description')) {
            $description = new VRACDescription;
            $description->text = $request->input('description');
            $description->save();
            $image->descriptions()->sync($description->id);
        }

        if ($request->filled('latitude') || $request->filled('longitude')) {
            $location = new Location;
            $location->latitude = $request->input('latitude');
            $location->longitude = $request->input('longitude');
            $location->label = $request->input('location_label');
            // $location->coordinates = '-23.580948, -46.637004';
            $location->save();
            $image->locations()->sync($location->id);
        }

        if ($request->filled('earliest_date')) {
            $date = new VRACDate;
            $date->type = 'creation';
            $date->earliest_date = $request->input('earliest_date');
            $date->circa_earliest_date = $request->input('circa');
            $date->latest_date = $request->input('latest_date');
            $date->circa_latest_date = $request->input('circa');
            $date->save();
            $image->dates()->sync($date->id);
        }

        // Create derivatives and capture their sizes
        $thumbInfo = $this->createDerivative($image, 300);
        $midInfo = $this->createDerivative($image, 1024);
        // Store sizes as JSON structure: original, mid, thumb
        $image->sizes = [
            'original' => [
                'width' => $origWidth,
                'height' => $origHeight,
            ],
            'mid' => [
                'width' => $midInfo['width'] ?? null,
                'height' => $midInfo['height'] ?? null,
            ],
            'thumb' => [
                'width' => $thumbInfo['width'] ?? null,
                'height' => $thumbInfo['height'] ?? null,
            ],
        ];
        $image->save();

        TileImage::dispatch($image);

        Cache::forget('locations.geojson');

        return new ImageResource($image);
    }

    /**
     * @unauthenticated
     */
    public function show(VRACImage $image)
    {
        $image->load([
            'agents.contributorName',
            'user',
            'collective',
            'culturalContexts',
            'dates',
            'descriptions',
            'titles',
            'techniques',
            'workTypes',
            'materials',
            'stylePeriods',
            'measurements',
            'stateEditions',
            'sources',
            'rights',
            'inscriptions',
            'subjects',
            'locations',
            'works.titles',
            'works.location',
        ]);

        return new ImageResource($image);
    }

    /*    public function update(UpdateImageRequest $request, VRACImage $image)
    {
        $image->user_id = $request->input('user_id');
        $image->collective_id = $request->input('collective_id');
        $image->save();

        // $path = $request->file('image')->storeAs(
        //     dirname($image->originalPath()), 'default.jpg', 'public'
        // );

        $title = $image->titles()->first();
        $title->label = $request->input('title');
        $title->type = 'other';
        $title->save();

        $photographerRole = VRACAgentRole::getPhotographer();
        $agentPhotographer = VRACAgent::firstOrCreate([
            'role_id' => $photographerRole->id,
            'contributor_name_id' => $request->input('photographer'),
        ]);
        $agentPhotographer->load('contributorName');
        $image->agents()->sync($agentPhotographer->id);

        $right = $image->rights()->first();
        $right->text = Str::upper($request->input('license'));
        $right->type = 'copyrighted';
        $right->href = 'https://creativecommons.org/licenses/' . Str::lower($request->input('license')) . '/4.0';
        $right->rights_holder = $agentPhotographer->contributorName->name;
        $right->save();

        $image->subjects()->sync($request->input('subjects'));

        if ($request->filled('description')) {
            $description = $image->descriptions()->first();
            $description->text = $request->input('description');
            $description->save();
        }

        if ($request->filled('latitude') || $request->filled('longitude')) {
            $location = $image->locations()->first();
            $location->latitude = $request->input('latitude');
            $location->longitude = $request->input('longitude');
            $location->label = $request->input('location_label');
            // $location->coordinates = '-23.580948, -46.637004';
            $location->save();
        }

        if ($request->filled('earliest_date')) {
            $date = $image->dates()->first();
            $date->type = 'creation';
            $date->earliest_date = $request->input('earliest_date');
            $date->circa_earliest_date = $request->input('circa');
            $date->latest_date = $request->input('latest_date');
            $date->circa_latest_date = $request->input('circa');
            $date->save();
        }

        Cache::forget('locations.geojson');

        return new ImageResource($image);
    }*/

    public function update(
        UpdateImageRequest $request,
        VRACImage $image,
        ApplyImageChangesAction $applyImageChanges
    ) {
        $image = $applyImageChanges->execute($image, $request->validated());

        return new ImageResource($image);
    }

    public function destroy(Request $request, VRACImage $image)
    {
        $isOwner = $request->user()->id === $image->user_id;
        $isCollectiveMember = $image->collective_id
            && $image->collective->isMember($request->user());

        if (! $isOwner && ! $isCollectiveMember) {
            abort(403);
        }

        Storage::disk('public')->deleteDirectory($image->path('base'));

        $image->delete();

        Cache::forget('locations.geojson');

        return new ImageResource($image);
    }

    /**
     * Sugestões de busca
     *
     * Retorna os termos mais usados por categoria: tipos de obra, materiais, técnicas, períodos,
     * contextos culturais, contribuidores e assuntos. Os assuntos vêm agrupados por categoria VRACore
     * (material, technique, work_type, style_period, uncategorized), top 10 por grupo.
     * Resultado cacheado por 24h.
     *
     * @group Imagens
     *
     * @unauthenticated
     */
    public function searchSuggestions()
    {
        return Cache::remember('image.search-suggestions', 86400, function () {
            $top = fn (string $pivot, string $table, string $fk, string $label) => DB::table($pivot)
                ->join($table, "{$pivot}.{$fk}", '=', "{$table}.id")
                ->select("{$table}.id", "{$table}.{$label} as term", DB::raw('COUNT(*) as total'))
                ->groupBy("{$table}.id", "{$table}.{$label}")
                ->orderByDesc('total')
                ->limit(10)
                ->get(['id', 'term']);

            $topSubjects = fn (string $joinTable, string $joinCol) => DB::table('image_subject')
                ->join('vrac_subjects', 'image_subject.subject_id', '=', 'vrac_subjects.id')
                ->join($joinTable, DB::raw('LOWER(vrac_subjects.term)'), '=', DB::raw("LOWER({$joinTable}.{$joinCol})"))
                ->select('vrac_subjects.id', 'vrac_subjects.term')
                ->groupBy('vrac_subjects.id', 'vrac_subjects.term')
                ->orderByDesc(DB::raw('COUNT(*)'))
                ->limit(10)
                ->get(['id', 'term']);

            $topSubjectsUncategorized = DB::table('image_subject')
                ->join('vrac_subjects', 'image_subject.subject_id', '=', 'vrac_subjects.id')
                ->leftJoin('vrac_materials', DB::raw('LOWER(vrac_subjects.term)'), '=', DB::raw('LOWER(vrac_materials.label)'))
                ->leftJoin('vrac_techniques', DB::raw('LOWER(vrac_subjects.term)'), '=', DB::raw('LOWER(vrac_techniques.label)'))
                ->leftJoin('vrac_work_types', DB::raw('LOWER(vrac_subjects.term)'), '=', DB::raw('LOWER(vrac_work_types.label)'))
                ->leftJoin('vrac_style_periods', DB::raw('LOWER(vrac_subjects.term)'), '=', DB::raw('LOWER(vrac_style_periods.label)'))
                ->whereNull('vrac_materials.id')
                ->whereNull('vrac_techniques.id')
                ->whereNull('vrac_work_types.id')
                ->whereNull('vrac_style_periods.id')
                ->select('vrac_subjects.id', 'vrac_subjects.term')
                ->groupBy('vrac_subjects.id', 'vrac_subjects.term')
                ->orderByDesc(DB::raw('COUNT(*)'))
                ->limit(10)
                ->get(['id', 'term']);

            $subjects = [
                'material' => $topSubjects('vrac_materials', 'label'),
                'technique' => $topSubjects('vrac_techniques', 'label'),
                'work_type' => $topSubjects('vrac_work_types', 'label'),
                'style_period' => $topSubjects('vrac_style_periods', 'label'),
                'uncategorized' => $topSubjectsUncategorized,
            ];

            return response()->json([
                'work_types' => $top('image_work_type', 'vrac_work_types', 'work_type_id', 'label'),
                'materials' => $top('image_material', 'vrac_materials', 'material_id', 'label'),
                'techniques' => $top('image_technique', 'vrac_techniques', 'technique_id', 'label'),
                'style_periods' => $top('image_style_period', 'vrac_style_periods', 'style_period_id', 'label'),
                'cultural_contexts' => $top('cultural_context_image', 'vrac_cultural_contexts', 'cultural_context_id', 'label'),
                'contributors' => DB::table('agent_image')
                    ->join('vrac_agents', 'agent_image.agent_id', '=', 'vrac_agents.id')
                    ->join('vrac_contributor_names', 'vrac_agents.contributor_name_id', '=', 'vrac_contributor_names.id')
                    ->select('vrac_contributor_names.id', 'vrac_contributor_names.name as term', DB::raw('COUNT(*) as total'))
                    ->groupBy('vrac_contributor_names.id', 'vrac_contributor_names.name')
                    ->orderByDesc('total')
                    ->limit(10)
                    ->get(['id', 'term']),
                'subjects' => $subjects,
            ]);
        });
    }

    /**
     * Imagens relacionadas
     *
     * Retorna até 50 imagens relacionadas à imagem informada, paginadas de 10 em 10.
     * A ordem é determinada por score ponderado: subjects (3pts), estilo/tipologia (2pts), demais campos (1pt).
     *
     * @group Imagens
     *
     * @unauthenticated
     */
    public function related(VRACImage $image)
    {
        $id = $image->id;

        $weightedPivots = [
            ['table' => 'image_subject',         'col' => 'subject_id',          'weight' => 3],
            ['table' => 'image_style_period',     'col' => 'style_period_id',     'weight' => 2],
            ['table' => 'image_work_type',        'col' => 'work_type_id',        'weight' => 2],
            ['table' => 'image_material',         'col' => 'material_id',         'weight' => 1],
            ['table' => 'image_technique',        'col' => 'technique_id',        'weight' => 1],
            ['table' => 'agent_image',            'col' => 'agent_id',            'weight' => 1],
            ['table' => 'image_location',         'col' => 'location_id',         'weight' => 1],
            ['table' => 'cultural_context_image', 'col' => 'cultural_context_id', 'weight' => 1],
            ['table' => 'image_work',             'col' => 'work_id',             'weight' => 1],
        ];

        $scores = [];

        foreach ($weightedPivots as $pivot) {
            $matchedIds = DB::table($pivot['table'].' as p2')
                ->select('p2.image_id')
                ->join($pivot['table'].' as p1', "p1.{$pivot['col']}", '=', "p2.{$pivot['col']}")
                ->where('p1.image_id', $id)
                ->where('p2.image_id', '!=', $id)
                ->pluck('p2.image_id');

            foreach ($matchedIds as $matchedId) {
                $scores[$matchedId] = ($scores[$matchedId] ?? 0) + $pivot['weight'];
            }
        }

        arsort($scores);
        $topIds = array_slice(array_keys($scores), 0, 50);

        if (empty($topIds)) {
            return ImageResource::collection(collect());
        }

        $placeholders = implode(',', array_fill(0, count($topIds), '?'));

        $images = VRACImage::whereIn('id', $topIds)
            ->orderByRaw("FIELD(id, {$placeholders})", $topIds)
            ->paginate(10);

        return ImageResource::collection($images);
    }

    public function downloadFull(Request $request, string $id)
    {
        $image = VRACImage::find($id);

        $path = $image->path('original', 'absolute');

        return response()->download($path, 'imagem-'.$id);
    }

    private function createDerivative(VRACImage $image, int $size = 300): array
    {
        // Create a thumbnail with vips and write to the appropriate storage path.
        $thumbnail = VipsImage::thumbnail($image->path('original', 'absolute'), $size);
        $width = $thumbnail->width;
        $height = $thumbnail->height;

        // relative IIIF-style path for the derivative (storage relative under images/iiif/{id})
        $relPath = $image->path('thumb', 'relative', ['width' => $width, 'height' => $height]);

        $destination = $image->path('thumb', 'absolute', ['width' => $width, 'height' => $height]);
        if (! file_exists(dirname($destination))) {
            // Group-writable so the queue worker (www-data) and any maintenance
            // command share write access under images/iiif. The IIIF root carries
            // the setgid bit so new dirs inherit the www-data group.
            mkdir(dirname($destination), 0775, true);
        }
        $thumbnail->writeToFile($destination);

        return [
            'rel_path' => $relPath,
            'width' => $width,
            'height' => $height,
        ];
    }
}
