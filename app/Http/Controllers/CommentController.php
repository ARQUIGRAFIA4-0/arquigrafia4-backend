<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\StoreCommentRequest;
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

        return response()->json($comments);
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

        return response()->json(
            $comment->load('user'),
            201
        );
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    { {
            // Garante que só o dono pode deletar
            if ($comment->user_id !== $request->user()->id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            $comment->delete(); // soft delete — deleted_at é preenchido

            return response()->json(null, 204);
        }
    }
}
