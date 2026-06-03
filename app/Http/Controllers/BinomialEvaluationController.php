<?php

namespace App\Http\Controllers;

use App\Http\Requests\Binomial\StoreBinomialEvaluationRequest;
use App\Models\Binomial;
use App\Models\BinomialEvaluation;
use App\Models\VRACore\VRACImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BinomialEvaluationController extends Controller
{
    /**
     * Promedio general de binomios
     *
     * Retorna os binômios com o valor médio de todas as avaliações da imagem.
     * Se o usuário estiver autenticado, inclui sua própria avaliação em `my_value`.
     * Se não houver avaliações, retorna `data: null`.
     *
     * @group Binomials
     * @unauthenticated
     *
     * @urlParam image string required UUID da imagem. Example: 000f9800-5186-4ebf-8f19-8e324749b846
     */
    public function index(Request $request, VRACImage $image): JsonResponse
    {
        $userId = auth('api')->id();

        $binomials = Binomial::where('active', true)
            ->orderBy('order')
            ->get();

        $totalEvaluators = BinomialEvaluation::where('image_id', $image->id)
            ->distinct('user_id')
            ->count('user_id');

        if ($totalEvaluators === 0) {
            return response()->json([
                'data'              => null,
                'total_evaluators'  => 0,
            ]);
        }

        // Averages por binomio
        $averages = BinomialEvaluation::where('image_id', $image->id)
            ->selectRaw('binomial_id, ROUND(AVG(value), 1) as average')
            ->groupBy('binomial_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->binomial_id => (float) $row->average])
            ->toArray();

        // Evaluación propia del usuario
        $myValues = [];
        if ($userId) {
            $myValues = BinomialEvaluation::where('image_id', $image->id)
                ->where('user_id', $userId)
                ->pluck('value', 'binomial_id')
                ->toArray();
        }

        $data = $binomials->map(fn ($b) => [
            'id'         => $b->id,
            'word_left'  => $b->word_left,
            'word_right' => $b->word_right,
            'order'      => $b->order,
            'average'    => $averages[$b->id] ?? null,
            'my_value'   => $myValues[$b->id] ?? null,
        ]);

        return response()->json([
            'data'             => $data,
            'total_evaluators' => $totalEvaluators,
        ]);
    }

    /**
     * Enviar avaliação de binômios
     *
     * Cria ou atualiza a avaliação do usuário autenticado para a imagem.
     * Deve enviar todos os binômios em um único envio. Se o usuário já avaliou, os valores são atualizados.
     *
     * @group Binomials
     * @authenticated
     *
     * @urlParam image string required UUID da imagem. Example: 000f9800-5186-4ebf-8f19-8e324749b846
     * @bodyParam evaluations array required Lista de avaliações, uma por binômio.
     * @bodyParam evaluations[].binomial_id integer required ID do binômio. Example: 1
     * @bodyParam evaluations[].value integer required Valor entre 0 e 100. Example: 75
     */
    public function store(StoreBinomialEvaluationRequest $request, VRACImage $image): JsonResponse
    {
        $userId  = $request->user()->id;
        $created = false;
        $updated = false;

        foreach ($request->evaluations as $evaluation) {
            $record = BinomialEvaluation::updateOrCreate(
                [
                    'image_id'    => $image->id,
                    'user_id'     => $userId,
                    'binomial_id' => $evaluation['binomial_id'],
                ],
                [
                    'value' => $evaluation['value'],
                ]
            );

            $record->wasRecentlyCreated ? $created = true : $updated = true;
        }

        $message = match (true) {
            $created && !$updated => 'Evaluación guardada correctamente.',
            !$created && $updated => 'Evaluación actualizada correctamente.',
            default               => 'Evaluación guardada y actualizada correctamente.',
        };

        $myEvaluations = BinomialEvaluation::where('image_id', $image->id)
            ->where('user_id', $userId)
            ->with('binomial')
            ->get()
            ->map(fn ($e) => [
                'binomial_id' => $e->binomial_id,
                'word_left'   => $e->binomial->word_left,
                'word_right'  => $e->binomial->word_right,
                'order'       => $e->binomial->order,
                'value'       => $e->value,
            ])
            ->sortBy('order')
            ->values();

        return response()->json([
            'message' => $message,
            'data'    => $myEvaluations,
        ]);
    }

    /**
     * Relatório de médias por data
     *
     * Retorna a média das avaliações de binômios da imagem com filtros opcionais de ano e mês.
     * Se não houver avaliações no período, retorna `data: null`.
     *
     * @group Binomials
     * @unauthenticated
     *
     * @urlParam image string required UUID da imagem. Example: 000f9800-5186-4ebf-8f19-8e324749b846
     * @queryParam year integer Filtrar por ano. Example: 2026
     * @queryParam month integer Filtrar por mês (1-12). Example: 5
     */
    public function report(Request $request, VRACImage $image): JsonResponse
    {
        $request->validate([
            'year'  => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $binomials = Binomial::where('active', true)->orderBy('order')->get();
        $query     = $this->applyDateFilters(
            BinomialEvaluation::where('image_id', $image->id),
            $request
        );

        $totalEvaluators = (clone $query)->distinct('user_id')->count('user_id');
        $filters = [
            'year'  => $request->filled('year') ? (int) $request->year : null,
            'month' => $request->filled('month') ? (int) $request->month : null,
        ];

        if ($totalEvaluators === 0) {
            return response()->json([
                'data'             => null,
                'total_evaluators' => 0,
                'filters'          => $filters,
            ]);
        }

        $averages = (clone $query)
            ->selectRaw('binomial_id, ROUND(AVG(value), 1) as average')
            ->groupBy('binomial_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->binomial_id => (float) $row->average])
            ->toArray();

        $data = $binomials->map(fn ($b) => [
            'id'         => $b->id,
            'word_left'  => $b->word_left,
            'word_right' => $b->word_right,
            'order'      => $b->order,
            'average'    => $averages[$b->id] ?? null,
        ]);

        return response()->json([
            'data'             => $data,
            'total_evaluators' => $totalEvaluators,
            'filters'          => $filters,
        ]);
    }

    /**
     * Relatório de avaliações individuais
     *
     * Retorna todas as avaliações individuais da imagem de forma anonimizada, junto com as médias por binômio.
     * Suporta filtros opcionais de ano e mês. Cada item em `evaluations` representa um avaliador (linha do gráfico).
     * Se não houver avaliações no período, retorna `evaluations: []`.
     *
     * @group Binomials
     * @unauthenticated
     *
     * @urlParam image string required UUID da imagem. Example: 000f9800-5186-4ebf-8f19-8e324749b846
     * @queryParam year integer Filtrar por ano. Example: 2026
     * @queryParam month integer Filtrar por mês (1-12). Example: 5
     */
    public function evaluations(Request $request, VRACImage $image): JsonResponse
    {
        $request->validate([
            'year'  => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $binomials = Binomial::where('active', true)->orderBy('order')->get();
        $query     = $this->applyDateFilters(
            BinomialEvaluation::where('image_id', $image->id),
            $request
        );

        $totalEvaluators = (clone $query)->distinct('user_id')->count('user_id');
        $filters = [
            'year'  => $request->filled('year') ? (int) $request->year : null,
            'month' => $request->filled('month') ? (int) $request->month : null,
        ];

        if ($totalEvaluators === 0) {
            return response()->json([
                'binomials'        => $binomials->map(fn ($b) => [
                    'id'         => $b->id,
                    'word_left'  => $b->word_left,
                    'word_right' => $b->word_right,
                    'order'      => $b->order,
                ]),
                'evaluations'      => [],
                'averages'         => null,
                'total_evaluators' => 0,
                'filters'          => $filters,
            ]);
        }

        // Agrupar evaluaciones por usuario (anonimizado)
        $rawEvaluations = (clone $query)->get();

        $evaluations = $rawEvaluations
            ->groupBy('user_id')
            ->map(fn ($userEvals) => [
                'values' => $userEvals->pluck('value', 'binomial_id'),
            ])
            ->values();

        // Promedios
        $averages = (clone $query)
            ->selectRaw('binomial_id, ROUND(AVG(value), 1) as average')
            ->groupBy('binomial_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->binomial_id => (float) $row->average])
            ->toArray();

        return response()->json([
            'binomials' => $binomials->map(fn ($b) => [
                'id'         => $b->id,
                'word_left'  => $b->word_left,
                'word_right' => $b->word_right,
                'order'      => $b->order,
            ]),
            'evaluations'      => $evaluations,
            'averages'         => $averages,
            'total_evaluators' => $totalEvaluators,
            'filters'          => $filters,
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function applyDateFilters($query, Request $request)
    {
        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }

        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month);
        }

        return $query;
    }
}
