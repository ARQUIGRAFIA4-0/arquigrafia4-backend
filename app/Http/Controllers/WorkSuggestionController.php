<?php

namespace App\Http\Controllers;

use App\Actions\Works\ApplyWorkChangesAction;
use App\Http\Resources\WorkSuggestionResource;
use App\Models\Location;
use App\Models\VRACore\VRACSubject;
use App\Models\VRACore\VRACWork;
use App\Models\WorkSuggestion;
use App\Support\WorkDeduplication;
use App\Support\WorkUpdateRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WorkSuggestionController extends Controller
{
    /**
     * List work suggestions
     *
     * @group Work Suggestions
     *
     * @unauthenticated
     *
     * @queryParam work_id string Filter by work.
     * @queryParam status string Filter by status (pending, accepted, partially_accepted, rejected).
     */
    public function index(Request $request)
    {
        $query = WorkSuggestion::with(['work', 'user', 'reviewer']);

        if ($request->filled('work_id')) {
            $query->where('work_id', $request->input('work_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $suggestions = $query->latest()->paginate(20);

        $this->hydrateSubjects($suggestions->getCollection());

        return WorkSuggestionResource::collection($suggestions);
    }

    /**
     * Create work suggestion
     *
     * @group Work Suggestions
     *
     * @authenticated
     *
     * @bodyParam payload object required Suggested changes.
     */
    public function store(Request $request, VRACWork $work)
    {
        $request->validate([
            'payload' => ['required', 'array'],
        ]);

        $suggestion = new WorkSuggestion;
        $suggestion->id = (string) Str::uuid();
        $suggestion->work_id = $work->id;
        $suggestion->user_id = $request->user()->id;
        $suggestion->status = WorkSuggestion::STATUS_PENDING;
        $suggestion->payload = $request->input('payload');
        $suggestion->save();

        return response()->json([
            'suggestion' => $suggestion,
        ], 201);
    }

    /**
     * Get work suggestion
     *
     * @group Work Suggestions
     *
     * @unauthenticated
     */
    public function show(WorkSuggestion $workSuggestion)
    {
        $workSuggestion->load(['work', 'user', 'reviewer']);

        $this->hydrateSubjects(collect([$workSuggestion]));

        return new WorkSuggestionResource($workSuggestion);
    }

    /**
     * Update work suggestion
     *
     * @group Work Suggestions
     *
     * @authenticated
     *
     * @bodyParam payload object required Suggested changes.
     */
    public function update(Request $request, WorkSuggestion $workSuggestion)
    {
        if ($workSuggestion->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $workSuggestion->isPending()) {
            return response()->json([
                'message' => 'Apenas sugestões pendentes podem ser editadas.',
            ], 422);
        }

        $request->validate([
            'payload' => ['required', 'array'],
        ]);

        $workSuggestion->payload = $request->input('payload');
        $workSuggestion->save();

        return response()->json([
            'suggestion' => $workSuggestion,
        ]);
    }

    /**
     * Delete work suggestion
     *
     * @group Work Suggestions
     *
     * @authenticated
     */
    public function destroy(Request $request, WorkSuggestion $workSuggestion)
    {
        if ($workSuggestion->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $workSuggestion->isPending()) {
            return response()->json([
                'message' => 'Apenas sugestões pendentes podem ser excluídas.',
            ], 422);
        }

        $workSuggestion->delete();

        return response()->json([
            'suggestion' => $workSuggestion,
        ]);
    }

    /**
     * Accept work suggestion
     *
     * Acceptable by any user who owns an image related to the work.
     *
     * @group Work Suggestions
     *
     * @authenticated
     *
     * @bodyParam accepted_fields array Optional fields to accept.
     * @bodyParam review_note string Optional review note.
     */
    public function accept(
        Request $request,
        WorkSuggestion $workSuggestion,
        ApplyWorkChangesAction $applyWorkChanges
    ) {
        $request->validate([
            'accepted_fields' => ['nullable', 'array'],
            'accepted_fields.*' => ['string'],
            'review_note' => ['nullable', 'string'],
        ]);

        if (! $workSuggestion->isPending()) {
            return response()->json([
                'message' => 'A sugestão já foi revisada.',
            ], 422);
        }

        $this->authorizeReviewer($request, $workSuggestion);

        $work = $workSuggestion->work;

        $originalPayload = $workSuggestion->payload ?? [];
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
            WorkUpdateRules::rules()
        )->validate();

        // Resulting state after applying the accepted fields: request values
        // where present, the work's current values otherwise.
        $titleIds = array_key_exists('titles', $validated)
            ? $validated['titles']
            : $work->titles()->pluck('vrac_titles.id')->all();

        if (array_key_exists('titles', $validated)) {
            WorkUpdateRules::assertExactlyOnePrimaryTitle($titleIds);
        }

        $locationId = array_key_exists('location_id', $validated)
            ? $validated['location_id']
            : $work->location_id;

        $location = $locationId ? Location::find($locationId) : null;

        $duplicate = WorkDeduplication::findDuplicate(
            $titleIds,
            $location?->latitude !== null ? (float) $location->latitude : null,
            $location?->longitude !== null ? (float) $location->longitude : null,
            $work->id
        );

        if ($duplicate) {
            return response()->json([
                'message' => 'Já existe uma obra com este título principal neste local.',
                'existing_work' => $duplicate->load(VRACWork::RELATIONS),
            ], 422);
        }

        $updatedWork = null;

        DB::transaction(function () use (
            $request,
            $workSuggestion,
            $work,
            $validated,
            $applyWorkChanges,
            $originalPayload,
            $payloadToApply,
            &$updatedWork
        ) {
            $updatedWork = $applyWorkChanges->execute($work, $validated);

            $acceptedAll = count($payloadToApply) === count($originalPayload);

            $workSuggestion->status = $acceptedAll
                ? WorkSuggestion::STATUS_ACCEPTED
                : WorkSuggestion::STATUS_PARTIALLY_ACCEPTED;
            $workSuggestion->reviewed_by = $request->user()->id;
            $workSuggestion->reviewed_at = now();
            $workSuggestion->review_note = $request->input('review_note');
            $workSuggestion->save();
        });

        return response()->json([
            'message' => 'Sugestão revisada com sucesso.',
            'suggestion' => $workSuggestion->fresh(['work', 'user', 'reviewer']),
            'work' => $updatedWork,
        ]);
    }

    /**
     * Reject work suggestion
     *
     * Rejectable by any user who owns an image related to the work.
     *
     * @group Work Suggestions
     *
     * @authenticated
     *
     * @bodyParam review_note string Optional review note.
     */
    public function reject(Request $request, WorkSuggestion $workSuggestion)
    {
        $request->validate([
            'review_note' => ['nullable', 'string'],
        ]);

        if (! $workSuggestion->isPending()) {
            return response()->json([
                'message' => 'A sugestão já foi revisada.',
            ], 422);
        }

        $this->authorizeReviewer($request, $workSuggestion);

        $workSuggestion->status = WorkSuggestion::STATUS_REJECTED;
        $workSuggestion->reviewed_by = $request->user()->id;
        $workSuggestion->reviewed_at = now();
        $workSuggestion->review_note = $request->input('review_note');
        $workSuggestion->save();

        return response()->json([
            'message' => 'Sugestão rejeitada com sucesso.',
            'suggestion' => $workSuggestion->fresh(['work', 'user', 'reviewer']),
        ]);
    }

    /**
     * A work suggestion may be reviewed by any user who owns an image
     * related to the work, except its own author.
     */
    private function authorizeReviewer(Request $request, WorkSuggestion $workSuggestion): void
    {
        $userId = $request->user()->id;

        if ($workSuggestion->user_id === $userId) {
            abort(403, 'Você não pode revisar a sua própria sugestão.');
        }

        $ownsRelatedImage = $workSuggestion->work->images()
            ->where('vrac_images.user_id', $userId)
            ->exists();

        abort_unless($ownsRelatedImage, 403);
    }

    /**
     * Replace subject UUIDs in each suggestion payload with hydrated
     * VRACSubject records, mirroring the image-suggestion behaviour.
     */
    private function hydrateSubjects($suggestions): void
    {
        $subjectIds = $suggestions
            ->flatMap(fn ($s) => $s->payload['subjects'] ?? [])
            ->unique()
            ->values();

        if ($subjectIds->isEmpty()) {
            return;
        }

        $subjects = VRACSubject::whereIn('id', $subjectIds)
            ->get(['id', 'term', 'type', 'vocab', 'ref_id', 'source'])
            ->keyBy('id');

        $suggestions->each(function ($suggestion) use ($subjects) {
            $payload = $suggestion->payload ?? [];

            if (! empty($payload['subjects'])) {
                $payload['subjects'] = collect($payload['subjects'])
                    ->map(fn ($id) => $subjects->get($id))
                    ->filter()
                    ->values();

                $suggestion->payload = $payload;
            }
        });
    }
}
