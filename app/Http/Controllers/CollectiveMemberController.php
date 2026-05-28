<?php

namespace App\Http\Controllers;

use App\Http\Requests\Collective\RemoveMemberRequest;
use App\Http\Requests\Collective\UpdateMemberRequest;
use App\Http\Resources\CollectiveResource;
use App\Models\Collective;
use App\Models\User;

/**
 * @group Membros de Coletivos
 *
 * Listagem, atualização e remoção de membros de coletivos.
 */
class CollectiveMemberController extends Controller
{
    public function index(Collective $collective)
    {
        if (!$collective->isMember(request()->user())) {
            abort(403);
        }

        $collective->load('members');

        return new CollectiveResource($collective);
    }

    public function update(UpdateMemberRequest $request, Collective $collective, User $user)
    {
        if (!$collective->isMember($user)) {
            abort(404);
        }

        // Prevent demoting the last admin
        if ($request->input('role') === 'member' && $collective->isAdmin($user)) {
            if ($collective->admins()->count() <= 1) {
                abort(422, 'O coletivo deve ter pelo menos um admin.');
            }
        }

        $collective->members()->updateExistingPivot($user->id, [
            'role' => $request->input('role'),
        ]);

        $collective->load('members');

        return new CollectiveResource($collective);
    }

    public function destroy(RemoveMemberRequest $request, Collective $collective, User $user)
    {
        if (!$collective->isMember($user)) {
            abort(404);
        }

        // Prevent removing the last admin
        if ($collective->isAdmin($user) && $collective->admins()->count() <= 1) {
            abort(422, 'O coletivo deve ter pelo menos um admin.');
        }

        $collective->members()->detach($user->id);

        $collective->load('members');

        return new CollectiveResource($collective);
    }
}
