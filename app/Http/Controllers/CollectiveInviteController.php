<?php

namespace App\Http\Controllers;

use App\Http\Requests\Collective\RevokeInviteRequest;
use App\Http\Requests\Collective\StoreInviteRequest;
use App\Http\Resources\CollectiveInviteResource;
use App\Http\Resources\CollectiveResource;
use App\Models\Collective;
use App\Models\CollectiveInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group Convites de Coletivos
 *
 * Criação, listagem e resgate de convites para coletivos.
 */
class CollectiveInviteController extends Controller
{
    public function index(Collective $collective)
    {
        if (!$collective->isAdmin(request()->user())) {
            abort(403);
        }

        $invites = $collective->invites()->latest()->get();

        return CollectiveInviteResource::collection($invites);
    }

    public function store(StoreInviteRequest $request, Collective $collective)
    {
        $invite = new CollectiveInvite();
        $invite->collective_id = $collective->id;
        $invite->created_by = $request->user()->id;
        $invite->token = Str::random(64);
        $invite->is_single_use = $request->input('is_single_use');
        $invite->max_uses = $request->input('max_uses');
        $invite->expires_at = $request->input('expires_at', now()->addDay());
        $invite->save();

        return new CollectiveInviteResource($invite);
    }

    public function destroy(RevokeInviteRequest $request, Collective $collective, CollectiveInvite $invite)
    {
        $invite->revoked_at = now();
        $invite->save();

        return new CollectiveInviteResource($invite);
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $invite = CollectiveInvite::where('token', $request->input('token'))->firstOrFail();

        if (!$invite->isValid()) {
            abort(422, 'This invite link is no longer valid.');
        }

        $collective = $invite->collective;
        $user = $request->user();

        if ($collective->isMember($user)) {
            abort(422, 'You are already a member of this collective.');
        }

        $invite->redeem();
        $collective->members()->attach($user->id, ['role' => 'member']);

        $collective->load('members');

        return new CollectiveResource($collective);
    }
}
