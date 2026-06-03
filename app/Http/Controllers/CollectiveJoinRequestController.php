<?php

namespace App\Http\Controllers;

use App\Http\Requests\Collective\StoreJoinRequestRequest;
use App\Http\Requests\Collective\UpdateJoinRequestRequest;
use App\Http\Resources\UserResource;
use App\Models\Collective;
use App\Models\CollectiveJoinRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * @group Gestão de coletivos
 *
 * Manage join requests for collective membership. Users can request to join a collective,
 * and admins can approve or reject these requests. This replaces the previous invite link system.
 */
class CollectiveJoinRequestController extends Controller
{
    /**
     * Request to join a collective
     *
     * Submit a join request to become a member of a collective. Only authenticated users who are
     * not already members can submit a request. Each user can only have one pending request per
     * collective at a time.
     *
     * @urlParam collective required The ID of the collective. Example: 019d9127-505f-723e-81e7-240866fcf510
     * @response 201 {"data": {"id": "019d9127-505f-723e-81e7-240866fcf510", "collective_id": "...", "user_id": "...", "status": "pending", "created_at": "2026-04-15T12:39:26Z", "updated_at": "2026-04-15T12:39:26Z"}}
     * @response 422 {"message": "You are already a member of this collective."} Already a member
     * @response 422 {"message": "You already have a pending join request for this collective."} Duplicate pending request
     */
    public function store(StoreJoinRequestRequest $request, Collective $collective): JsonResponse
    {
        $user = $request->user();

        if ($collective->isMember($user)) {
            abort(422, 'Você já é membro deste coletivo.');
        }

        $alreadyPending = CollectiveJoinRequest::where('collective_id', $collective->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            abort(422, 'Você já tem uma solicitação pendente para este coletivo.');
        }

        $joinRequest = CollectiveJoinRequest::create([
            'collective_id' => $collective->id,
            'user_id'       => $user->id,
            'status'        => 'pending',
        ]);

        return response()->json(['data' => $joinRequest], 201);
    }

    /**
     * List pending join requests
     *
     * Retrieve all pending join requests for a collective. Only collective admins can view this list.
     * Results are sorted by most recent first and include the requesting user's information.
     *
     * @authenticated
     * @urlParam collective required The ID of the collective. Example: 019d9127-505f-723e-81e7-240866fcf510
     * @response 200 {"data": [{"id": "...", "collective_id": "...", "user_id": "...", "status": "pending", "created_at": "2026-04-15T12:39:26Z", "updated_at": "2026-04-15T12:39:26Z", "user": {"id": "...", "name": "John Doe", "avatar_url": "https://.../avatars/users/....webp", "legacy_id": null, "created_at": "...", "updated_at": "..."}}]}
     * @response 403 Unauthorized — you must be a collective admin
     * @response 404 Collective not found
     */
    public function index(Collective $collective): JsonResponse
    {
        if (!$collective->isAdmin(request()->user())) {
            abort(403);
        }

        $requests = CollectiveJoinRequest::with('user')
            ->where('collective_id', $collective->id)
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(fn (CollectiveJoinRequest $r) => [
                'id'            => $r->id,
                'collective_id' => $r->collective_id,
                'user_id'       => $r->user_id,
                'status'        => $r->status,
                'created_at'    => $r->created_at,
                'updated_at'    => $r->updated_at,
                'user'          => new UserResource($r->user),
            ]);

        return response()->json(['data' => $requests]);
    }

    /**
     * Approve or reject a join request
     *
     * As a collective admin, approve or reject a pending join request. Approving adds the user
     * as a member of the collective. Rejecting denies the request without adding the user.
     *
     * @authenticated
     * @urlParam collective required The ID of the collective. Example: 019d9127-505f-723e-81e7-240866fcf510
     * @urlParam user required The ID of the user who made the request. Example: 019d9127-505f-723e-81e7-240866fcf510
     * @bodyParam action required The action to perform. Must be 'approve' or 'reject'. Example: approve
     * @response 200 {"data": {"id": "...", "collective_id": "...", "user_id": "...", "status": "approved", "created_at": "2026-04-15T12:39:26Z", "updated_at": "2026-04-15T12:39:26Z"}}
     * @response 403 Unauthorized — you must be a collective admin
     * @response 404 Join request not found or already processed
     * @response 422 Invalid action value
     */
    public function update(UpdateJoinRequestRequest $request, Collective $collective, User $user): JsonResponse
    {
        $joinRequest = CollectiveJoinRequest::where('collective_id', $collective->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $action = $request->input('action');

        if ($action === 'approve') {
            $collective->members()->attach($user->id, ['role' => 'member']);
            $joinRequest->update(['status' => 'approved']);
        } else {
            $joinRequest->update(['status' => 'rejected']);
        }

        return response()->json(['data' => $joinRequest->fresh()]);
    }

    /**
     * Cancel a join request
     *
     * Withdraw a pending join request. Only the user who made the request can cancel it.
     * This deletes the request record entirely.
     *
     * @authenticated
     * @urlParam collective required The ID of the collective. Example: 019d9127-505f-723e-81e7-240866fcf510
     * @urlParam user required Your own user ID (the requester). Example: 019d9127-505f-723e-81e7-240866fcf510
     * @response 200 {"data": null}
     * @response 403 Unauthorized — you can only cancel your own requests
     * @response 404 Join request not found or already processed
     */
    public function destroy(Collective $collective, User $user): JsonResponse
    {
        if (request()->user()->id !== $user->id) {
            abort(403);
        }

        $joinRequest = CollectiveJoinRequest::where('collective_id', $collective->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $joinRequest->delete();

        return response()->json(['data' => null], 200);
    }
}
