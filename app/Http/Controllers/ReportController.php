<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    protected array $morphMap = [
        'image' => \App\Models\VRACore\VRACImage::class,
    ];

    /**
     * Criar denúncia
     *
     * @group Reports
     * @authenticated
     *
     * @bodyParam reportable_type string required Tipo do conteúdo denunciado. Valores aceitos: `image`. Example: image
     * @bodyParam reportable_id string required UUID do conteúdo denunciado. Example: 940d841b-2c1e-4652-b7a9-0d0113cfa083
     * @bodyParam reason string required Motivo da denúncia. Valores aceitos: `copyright`, `inappropriate`, `spam`, `harassment`, `misinformation`, `other`. Example: spam
     * @bodyParam description string Descrição opcional com mais detalhes. Example: Esta imagen parece ser contenido duplicado.
     */
    public function store(Request $request)
    {
        $request->validate([
            'reportable_type' => ['required', 'string', Rule::in(array_keys($this->morphMap))],
            'reportable_id'   => ['required', 'string'],
            'reason'          => ['required', Rule::in([
                'copyright', 'inappropriate', 'spam', 'harassment', 'misinformation', 'other',
            ])],
            'description'     => ['nullable', 'string', 'max:1000'],
        ]);

        $modelClass = $this->morphMap[$request->reportable_type];
        $reportable = $modelClass::findOrFail($request->reportable_id);

        // No puede denunciar su propio contenido
        if (isset($reportable->user_id) && $reportable->user_id === $request->user()->id) {
            return response()->json([
                'message' => 'No puedes denunciar tu propio contenido.',
            ], 422);
        }

        // No puede denunciar el mismo contenido dos veces
        $alreadyReported = Report::where('reporter_id', $request->user()->id)
            ->where('reportable_type', $modelClass)
            ->where('reportable_id', $reportable->id)
            ->exists();

        if ($alreadyReported) {
            return response()->json([
                'message' => 'Ya has denunciado este contenido.',
            ], 422);
        }

        $report = new Report();
        $report->id = (string) Str::uuid();
        $report->reportable_type = $modelClass;
        $report->reportable_id = $reportable->id;
        $report->reporter_id = $request->user()->id;
        $report->reason = $request->reason;
        $report->description = $request->description;
        $report->status = 'pending';
        $report->save();

        return response()->json([
            'message' => 'Denuncia enviada correctamente.',
            'report'  => $report,
        ], 201);
    }

    /**
     * Buscar denúncia por ID
     *
     * @group Reports
     * @authenticated
     */
    public function show(Report $report)
    {
        $report->load(['reportable', 'reporter']);

        return response()->json([
            'report' => $report,
        ]);
    }

    /**
     * Listar denúncias
     *
     * @group Reports
     * @authenticated
     *
     * @queryParam status string Filtrar por estado. Valores aceitos: `pending`, `reviewed`, `resolved`, `dismissed`. Example: pending
     * @queryParam reportable_type string Filtrar por tipo de conteúdo. Valores aceitos: `image`. Example: image
     */
    public function index(Request $request)
    {
        $query = Report::with(['reportable', 'reporter'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('reportable_type') && isset($this->morphMap[$request->reportable_type])) {
            $query->where('reportable_type', $this->morphMap[$request->reportable_type]);
        }

        return response()->json([
            'reports' => $query->paginate(20),
        ]);
    }

    /**
     * Atualizar status de denúncia
     *
     * @group Reports
     * @authenticated
     *
     * @bodyParam status string required Novo status da denúncia. Valores aceitos: `reviewed`, `resolved`, `dismissed`. Example: resolved
     */
    public function update(Request $request, Report $report)
    {
        $request->validate([
            'status' => ['required', Rule::in(['reviewed', 'resolved', 'dismissed'])],
        ]);

        $report->status = $request->status;
        $report->save();

        return response()->json([
            'message' => 'Denuncia actualizada.',
            'report'  => $report,
        ]);
    }
}
