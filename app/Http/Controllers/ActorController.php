<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActorResource;
use App\Models\Collective;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @group Actors
 *
 * Lista unificada de usuários e coletivos.
 */
class ActorController extends Controller
{
    private const SORTABLE = ['name', 'created_at'];

    /**
     * Lista usuários e coletivos em uma única coleção paginada.
     *
     * Query params:
     * - q: busca por nome (LIKE, case-insensitive)
     * - type: 'user' ou 'collective' para restringir o tipo
     * - sort: 'name', '-name', 'created_at', '-created_at' (prefixo '-' = desc; padrão: 'name')
     * - per_page: itens por página
     *
     * @unauthenticated
     */
    public function index(Request $request)
    {
        $q       = trim((string) $request->query('q', ''));
        $type    = $request->query('type');
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));
        $page    = max(1, (int) $request->query('page', 1));

        [$sortColumn, $sortDir] = $this->resolveSort($request->query('sort'));

        $actors = collect();

        if ($type !== 'collective') {
            $userQuery = User::query()->with('profile.subjects');
            if ($q !== '') {
                $userQuery->whereRaw('LOWER(name) LIKE LOWER(?)', ['%' . $q . '%']);
            }
            $userQuery->get()->each(function (User $user) use (&$actors) {
                $user->type = 'user';
                $actors->push($user);
            });
        }

        if ($type !== 'user') {
            $collectiveQuery = Collective::query()->with('subjects');
            if ($q !== '') {
                $collectiveQuery->whereRaw('LOWER(name) LIKE LOWER(?)', ['%' . $q . '%']);
            }
            $collectiveQuery->get()->each(function (Collective $collective) use (&$actors) {
                $collective->type = 'collective';
                $actors->push($collective);
            });
        }

        $actors = $sortDir === 'asc'
            ? $actors->sortBy($sortColumn)
            : $actors->sortByDesc($sortColumn);

        $paginator = new LengthAwarePaginator(
            $actors->slice(($page - 1) * $perPage, $perPage)->values(),
            $actors->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return ActorResource::collection($paginator);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveSort(?string $sort): array
    {
        $sort = $sort ?: 'name';
        $dir  = 'asc';

        if (str_starts_with($sort, '-')) {
            $dir  = 'desc';
            $sort = substr($sort, 1);
        }

        if (!in_array($sort, self::SORTABLE, true)) {
            $sort = 'name';
            $dir  = 'asc';
        }

        return [$sort, $dir];
    }
}
