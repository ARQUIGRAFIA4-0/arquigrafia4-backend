<?php

namespace App\Http\Controllers;

use App\Models\ImageSuggestion;
use App\Models\VRACore\VRACImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Actions\Images\ApplyImageChangesAction;
use App\Http\Resources\ImageResource;
use App\Support\ImageUpdateRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImageSuggestionController extends Controller
{

    public function index(Request $request)
    {
        $query = ImageSuggestion::with(['image', 'user', 'reviewer']);

        if ($request->filled('image_id')) {
            $query->where('image_id', $request->input('image_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json([
            'suggestions' => $query->latest()->paginate(20),
        ]);
    }
    /**
     * Create image suggestion
     *
     * @group Image Suggestions
     * @authenticated
     *
     * @bodyParam payload object required Suggested changes.
     */
    public function store(Request $request, VRACImage $image)
    {
        $request->validate([
            'payload' => ['required', 'array'],
            'payload.license' => ['prohibited'],
        ]);

        $suggestion = new ImageSuggestion();
        $suggestion->id = (string) Str::uuid();
        $suggestion->image_id = $image->id;
        $suggestion->user_id = $request->user()->id;
        $suggestion->status = 'pending';
        $suggestion->payload = $request->input('payload');
        $suggestion->save();

        return response()->json([
            'suggestion' => $suggestion,
        ], 201);
    }
    /**
     * Get image suggestion
     *
     * @group Image Suggestions
     * @unauthenticated
     */
    public function show(ImageSuggestion $imageSuggestion)
    {
        $imageSuggestion->load(['image', 'user', 'reviewer']);

        return response()->json([
            'suggestion' => $imageSuggestion,
        ]);
    }
    /**
     * Update image suggestion
     *
     * @group Image Suggestions
     * @authenticated
     *
     * @bodyParam payload object required Suggested changes.
     */
    public function update(Request $request, ImageSuggestion $imageSuggestion)
    {
        if ($imageSuggestion->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($imageSuggestion->status !== 'pending') {
            return response()->json([
                'message' => 'Apenas sugestões pendentes podem ser editadas.',
            ], 422);
        }

        $request->validate([
            'payload' => ['required', 'array'],
            'payload.license' => ['prohibited'],
        ]);

        $imageSuggestion->payload = $request->input('payload');
        $imageSuggestion->save();

        return response()->json([
            'suggestion' => $imageSuggestion,
        ]);
    }
    /**
     * Delete image suggestion
     *
     * @group Image Suggestions
     * @authenticated
     */
    public function destroy(Request $request, ImageSuggestion $imageSuggestion)
    {
        if ($imageSuggestion->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($imageSuggestion->status !== 'pending') {
            return response()->json([
                'message' => 'Apenas sugestões pendentes podem ser excluídas.',
            ], 422);
        }

        $imageSuggestion->delete();

        return response()->json([
            'suggestion' => $imageSuggestion,
        ]);
    }
    /**
     * Accept image suggestion
     *
     * @group Image Suggestions
     * @authenticated
     *
     * @bodyParam accepted_fields array Optional fields to accept.
     * @bodyParam review_note string Optional review note.
     */
    public function accept(
        Request $request,
        ImageSuggestion $imageSuggestion,
        ApplyImageChangesAction $applyImageChanges
    ) {
        $request->validate([
            'accepted_fields' => ['nullable', 'array'],
            'accepted_fields.*' => ['string'],
            'review_note' => ['nullable', 'string'],
        ]);

        if ($imageSuggestion->status !== 'pending') {
            return response()->json([
                'message' => 'A sugestão já foi revisada.',
            ], 422);
        }

        $image = $imageSuggestion->image;

        if ($request->user()->id !== $image->user_id) {
            abort(403);
        }

        $originalPayload = $imageSuggestion->payload ?? [];
        $payloadToApply = $originalPayload;

        $acceptedFields = $request->input('accepted_fields');

        if (! empty($acceptedFields)) {
            $payloadToApply = array_intersect_key(
                $originalPayload,
                array_flip($acceptedFields)
            );
        }

        $validated = Validator::make(
            $payloadToApply,
            ImageUpdateRules::rules()
        )->validate();

        $updatedImage = null;

        DB::transaction(function () use (
            $request,
            $imageSuggestion,
            $image,
            $validated,
            $applyImageChanges,
            $originalPayload,
            $payloadToApply,
            &$updatedImage
        ) {
            $updatedImage = $applyImageChanges->execute($image, $validated);

            $acceptedAll = count($payloadToApply) === count($originalPayload);

            $imageSuggestion->status = $acceptedAll ? 'accepted' : 'partially_accepted';
            $imageSuggestion->reviewed_by = $request->user()->id;
            $imageSuggestion->reviewed_at = now();
            $imageSuggestion->review_note = $request->input('review_note');
            $imageSuggestion->save();
        });

        return response()->json([
            'message' => 'Sugestão revisada com sucesso.',
            'suggestion' => $imageSuggestion->fresh(['image', 'user', 'reviewer']),
            'image' => new ImageResource($updatedImage),
        ]);
    }
    /**
     * Reject image suggestion
     *
     * @group Image Suggestions
     * @authenticated
     *
     * @bodyParam review_note string Optional review note.
     */
    public function reject(Request $request, ImageSuggestion $imageSuggestion)
    {
        $request->validate([
            'review_note' => ['nullable', 'string'],
        ]);

        if ($imageSuggestion->status !== 'pending') {
            return response()->json([
                'message' => 'A sugestão já foi revisada.',
            ], 422);
        }

        $image = $imageSuggestion->image;

        if ($request->user()->id !== $image->user_id) {
            abort(403);
        }

        $imageSuggestion->status = 'rejected';
        $imageSuggestion->reviewed_by = $request->user()->id;
        $imageSuggestion->reviewed_at = now();
        $imageSuggestion->review_note = $request->input('review_note');
        $imageSuggestion->save();

        return response()->json([
            'message' => 'Sugestão rejeitada com sucesso.',
            'suggestion' => $imageSuggestion->fresh(['image', 'user', 'reviewer']),
        ]);
    }
}
