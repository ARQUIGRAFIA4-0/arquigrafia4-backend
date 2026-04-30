<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentLikeController extends Controller
{
    public function store(Request $request, Comment $commentId): JsonResponse
    {
        $userId = $request->user()->id;

        // toggle - se já existe, remove; se não existe, adiciona
        if ($commentId->likes()->where('user_id', $userId)->exists()) {
            $commentId->likes()->detach($userId);
            $liked = false;
        } else {
            $commentId->likes()->attach($userId);
            $liked = true;
        }
        return response()->json([
            'liked' => $liked,
            'likes_count' => $commentId->likes()->count(),
        ]);
    }
}
