<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActorResource;
use App\Models\Collective;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $q    = trim((string) $request->query('q', ''));
        $type = $request->query('type');

        $columns = ['id', 'name', 'avatar_path', 'legacy_id', 'created_at', 'updated_at'];

        $users = User::query()
            ->select(array_merge($columns, [DB::raw("'user' as type")]));

        $collectives = Collective::query()
            ->select(array_merge($columns, [DB::raw("'collective' as type")]));

        if ($q !== '') {
            $like = '%' . $q . '%';
            $users->whereRaw('LOWER(name) LIKE LOWER(?)', [$like]);
            $collectives->whereRaw('LOWER(name) LIKE LOWER(?)', [$like]);
        }

        if ($type === 'user') {
            $query = $users;
        } elseif ($type === 'collective') {
            $query = $collectives;
        } else {
            $query = $users->unionAll($collectives);
        }

        [$sortColumn, $sortDir] = $this->resolveSort($request->query('sort'));
        $query->orderBy($sortColumn, $sortDir);

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        return ActorResource::collection($query->paginate($perPage)->appends($request->query()));
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
