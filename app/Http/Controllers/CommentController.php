<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use Illuminate\Http\Request;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $imageId): JsonResponse
    {
        $comments = Comment::with(['user', 'replies.user'])
            ->where('image_id', $imageId)
            ->whereNull('parent_id')
            ->latest()
            ->get();

        return CommentResource::collection($comments)->response();
    }

    /**
     * Display a listing of the replies.
     */
    public function replies(Comment $commentId): JsonResponse
    {
        $replies = $commentId->replies()
            ->with('user')
            ->latest()
            ->get();

        return CommentResource::collection($replies)->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommentRequest $request): JsonResponse
    {
        $comment = Comment::create([
            'user_id'   => $request->user()->id,
            'image_id'  => $request->image_id,
            'content'   => $request->content,
            'parent_id' => $request->parent_id, // null se for comentário raiz
        ]);

        return (new CommentResource($comment->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommentRequest $request, Comment $commentId): JsonResponse
    {
        $this->authorize('update', $commentId);

        // Não permite editar comentário já deletado logicamente
        if ($commentId->is_deleted) {
            return response()->json(['message' => 'Comentário removido não pode ser editado.'], 422);
        }

        $commentId->update([
            'content'   => $request->content,
            'edited_at' => now(),
        ]);

        return (new CommentResource($commentId->load('user')))->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Comment $commentId): JsonResponse
    {
        $this->authorize('delete', $commentId);

        if ($commentId->replies()->exists()) {
            $commentId->update([
                'content' => null,
                'is_deleted' => true,
            ]);
            return response()->json(null, 204);
        }

        $commentId->delete(); // soft delete normal
        return response()->json(null, 204);
    }
}
