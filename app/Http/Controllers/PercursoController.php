<?php

namespace App\Http\Controllers;

use App\Http\Resources\PercursoResource;
use App\Models\Album;
use App\Models\Percurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * @group Percursos
 *
 * Percursos fotográficos de um álbum: sequência de paradas (com ou sem imagem) e o trajeto desenhado no mapa.
 */
class PercursoController extends Controller
{
    /**
     * Percursos de um álbum
     *
     * Retorna a lista de percursos do álbum, na ordem em que foram salvos. Álbuns privados só são visíveis para quem os gerencia.
     *
     * @unauthenticated
     */
    public function index(Request $request, Album $album)
    {
        if ($album->is_private) {
            $user = $request->user('api');
            $canSee = $album->isOwnedByCollective()
                ? ($user && $album->collective->isMember($user))
                : ($user && $album->isOwnedByUser($user));
            if (!$canSee) abort(403);
        }

        $percursos = $album->percursos()->with('stops')->get();

        return response()->json(PercursoResource::collection($percursos)->resolve());
    }

    /**
     * Adicionar um percurso ao álbum
     *
     * Cria um novo percurso no final da lista do álbum, sem alterar os demais. Recebe um único objeto (não um array).
     * Coordenadas no formato `[longitude, latitude]`.
     *
     * @bodyParam title string Nome do percurso (opcional).
     * @bodyParam stops object[] required Paradas do percurso, no mínimo 2.
     * @bodyParam stops[].type string required Tipo da parada: `image` ou `custom`. Example: image
     * @bodyParam stops[].title string Título da parada. Example: Largo Páteo do Colégio
     * @bodyParam stops[].order integer required Posição da parada no percurso, a partir de 1. Example: 1
     * @bodyParam stops[].coordinates number[] required `[longitude, latitude]` da parada. Example: [-46.6331, -23.5482]
     * @bodyParam stops[].imageId string UUID da imagem. Obrigatório quando `type` for `image`; ignorado em `custom`.
     * @bodyParam route object required Trajeto desenhado no mapa.
     * @bodyParam route.isStreet boolean required Se o trajeto segue as ruas. Example: true
     * @bodyParam route.distanceMeters number Distância total em metros. Example: 382.2
     * @bodyParam route.durationSeconds number Duração estimada em segundos. Example: 61.6
     * @bodyParam route.coordinates number[][] required Pontos do trajeto, cada um como `[longitude, latitude]`.
     */
    public function store(Request $request, Album $album)
    {
        if (!$album->userCanManage($request->user())) {
            return response()->json(['message' => 'No tienes permiso para agregar percursos a este álbum.'], 403);
        }

        $data = $request->validate(array_merge(['id' => 'prohibited'], $this->itemRules()), $this->itemMessages());

        $position = ($album->percursos()->reorder()->max('position') ?? -1) + 1;

        $percurso = DB::transaction(fn () => $this->persist($album, $data, $position));

        return response()->json(
            (new PercursoResource($percurso->load('stops')))->resolve(),
            201
        );
    }

    /**
     * Salvar os percursos de um álbum
     *
     * Recebe a lista completa de percursos do álbum. Itens com `id` (que pertençam ao álbum) são atualizados
     * mantendo o mesmo id; itens sem `id` são criados; percursos do álbum que não vierem na lista são removidos.
     * As paradas de cada percurso são substituídas pelas enviadas. Coordenadas no formato `[longitude, latitude]`.
     *
     * O corpo da requisição é um **array na raiz** (não um objeto). Exemplo:
     *
     * ```json
     * [
     *   {
     *     "id": "UUID de um percurso existente do álbum (omita para criar um novo)",
     *     "title": null,
     *     "stops": [
     *       { "type": "image", "title": "Largo Páteo do Colégio", "order": 1, "coordinates": [-46.6331, -23.5482], "imageId": "UUID da imagem" },
     *       { "type": "custom", "title": "Ponto 2", "order": 2, "coordinates": [-46.6334, -23.5466], "imageId": null }
     *     ],
     *     "route": {
     *       "isStreet": true,
     *       "distanceMeters": 382.2,
     *       "durationSeconds": 61.6,
     *       "coordinates": [[-46.632536, -23.548279], [-46.633429, -23.546634]]
     *     }
     *   }
     * ]
     * ```
     *
     * Regras de cada item: `stops` é obrigatório (mínimo 2); `type` é `image` ou `custom`; `imageId` é obrigatório
     * (e deve existir) quando `type` for `image`, e vazio em `custom`; `order` começa em 1; `route.isStreet` e
     * `route.coordinates` são obrigatórios; `title`, `distanceMeters` e `durationSeconds` são opcionais.
     */
    public function sync(Request $request, Album $album)
    {
        if (!$album->userCanManage($request->user())) {
            return response()->json(['message' => 'No tienes permiso para modificar los percursos de este álbum.'], 403);
        }

        $data = $request->validate(array_merge(
            ['*' => 'array', '*.id' => 'nullable|uuid'],
            $this->itemRules('*.')
        ), $this->itemMessages('*.'));

        $items = array_values($data);
        $incomingIds = collect($items)->pluck('id')->filter()->values()->all();

        $foreignIds = array_diff($incomingIds, $album->percursos()->pluck('id')->all());
        if (!empty($foreignIds)) {
            return response()->json([
                'message'     => 'Algunos percursos no pertenecen a este álbum.',
                'invalid_ids' => array_values($foreignIds),
            ], 422);
        }

        DB::transaction(function () use ($album, $items, $incomingIds) {
            $album->percursos()->whereNotIn('id', $incomingIds)->delete();

            foreach ($items as $position => $item) {
                $existing = !empty($item['id']) ? $album->percursos()->findOrFail($item['id']) : null;
                $this->persist($album, $item, $position, $existing);
            }
        });

        return response()->json(
            PercursoResource::collection($album->percursos()->with('stops')->get())->resolve()
        );
    }

    /**
     * Remover um percurso do álbum
     *
     * Remove o percurso indicado (soft delete). Os demais percursos do álbum não são alterados.
     */
    public function destroy(Request $request, Album $album, Percurso $percurso)
    {
        if (!$album->userCanManage($request->user())) {
            return response()->json(['message' => 'No tienes permiso para eliminar percursos de este álbum.'], 403);
        }

        if ($percurso->album_id !== $album->id) {
            abort(404);
        }

        $percurso->delete();

        return response()->json(['message' => 'Percurso eliminado'], 200);
    }

    private function itemRules(string $prefix = ''): array
    {
        $maxStops = config('services.percursos.max_stops');
        $maxPoints = config('services.percursos.max_route_points');

        return [
            "{$prefix}title"                 => 'nullable|string|max:255',
            "{$prefix}stops"                 => "required|array|min:2|max:{$maxStops}",
            "{$prefix}stops.*.type"          => 'required|in:image,custom',
            "{$prefix}stops.*.title"         => 'nullable|string|max:255',
            "{$prefix}stops.*.order"         => 'required|integer|min:1',
            "{$prefix}stops.*.coordinates"   => 'required|array|size:2',
            "{$prefix}stops.*.coordinates.0" => 'required|numeric|between:-180,180',
            "{$prefix}stops.*.coordinates.1" => 'required|numeric|between:-90,90',
            "{$prefix}stops.*.imageId"       => [
                "required_if:{$prefix}stops.*.type,image", 'nullable', 'uuid',
                // Imagens apagadas (soft delete) não podem virar ponto do percurso.
                Rule::exists('vrac_images', 'id')->whereNull('deleted_at'),
            ],
            "{$prefix}route"                 => 'required|array',
            "{$prefix}route.isStreet"        => 'required|boolean',
            "{$prefix}route.distanceMeters"  => 'nullable|numeric|min:0',
            "{$prefix}route.durationSeconds" => 'nullable|numeric|min:0',
            // Sem regras curinga (route.coordinates.*.*): com milhares de pontos o validador do Laravel
            // fica superlinear (15 mil pontos ≈ 26 s). Uma regra só percorre o array uma vez.
            "{$prefix}route.coordinates"     => ['required', 'array', "max:{$maxPoints}", $this->routeCoordinatesRule()],
        ];
    }

    private function itemMessages(string $prefix = ''): array
    {
        return [
            "{$prefix}stops.max"             => 'O percurso pode ter no máximo :max paradas.',
            "{$prefix}route.coordinates.max" => 'O trajeto pode ter no máximo :max pontos.',
        ];
    }

    /**
     * Valida o formato de cada ponto e o comprimento total da linha (haversine), sem confiar no
     * distanceMeters enviado pelo cliente — vale também para o fallback em linha reta.
     */
    private function routeCoordinatesRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            $maxMeters = config('services.percursos.max_distance_km') * 1000;
            $total = 0.0;
            $previous = null;

            foreach (array_values($value) as $i => $point) {
                if (!is_array($point) || count($point) !== 2
                    || !is_numeric($point[0] ?? null) || !is_numeric($point[1] ?? null)
                    || abs($point[0]) > 180 || abs($point[1]) > 90) {
                    return $fail("O ponto {$i} do trajeto é inválido.");
                }

                if ($previous) {
                    $total += $this->haversineMeters($previous, $point);
                }
                $previous = $point;
            }

            if ($total > $maxMeters) {
                $fail(sprintf(
                    'O percurso tem %s km. O máximo permitido é %s km.',
                    number_format($total / 1000, 1, ',', '.'),
                    number_format($maxMeters / 1000, 0, ',', '.')
                ));
            }
        };
    }

    private function haversineMeters(array $a, array $b): float
    {
        $lat1 = deg2rad($a[1]);
        $lat2 = deg2rad($b[1]);
        $h = sin(($lat2 - $lat1) / 2) ** 2
            + cos($lat1) * cos($lat2) * sin(deg2rad($b[0] - $a[0]) / 2) ** 2;

        return 2 * 6371008.8 * asin(min(1, sqrt($h)));
    }

    private function persist(Album $album, array $item, int $position, ?Percurso $percurso = null): Percurso
    {
        $attributes = [
            'position'          => $position,
            'title'             => $item['title'] ?? null,
            'is_street'         => $item['route']['isStreet'],
            'distance_meters'   => $item['route']['distanceMeters'] ?? null,
            'duration_seconds'  => $item['route']['durationSeconds'] ?? null,
            'route_coordinates' => $item['route']['coordinates'],
        ];

        if ($percurso) {
            $percurso->update($attributes);
            $percurso->stops()->delete();
        } else {
            $percurso = $album->percursos()->create($attributes);
        }

        foreach ($item['stops'] as $stop) {
            $percurso->stops()->create([
                'type'      => $stop['type'],
                'title'     => $stop['title'] ?? null,
                'order'     => $stop['order'],
                'longitude' => $stop['coordinates'][0],
                'latitude'  => $stop['coordinates'][1],
                'image_id'  => $stop['type'] === 'image' ? $stop['imageId'] : null,
            ]);
        }

        return $percurso;
    }
}
