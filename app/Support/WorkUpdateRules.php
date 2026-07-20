<?php

namespace App\Support;

use App\Models\VRACore\VRACTitle;
use Illuminate\Validation\ValidationException;

class WorkUpdateRules
{
    /**
     * Validate that a set of title UUIDs contains exactly one primary
     * (pref = true) title. A work must have one — and only one — primary name.
     *
     * Throws a ValidationException keyed on `titles` when the rule is violated,
     * so it surfaces as a 422 exactly like the array/exists rules above.
     *
     * @param  array<int, string>  $titleIds
     */
    public static function assertExactlyOnePrimaryTitle(array $titleIds): void
    {
        $primaryCount = VRACTitle::whereIn('id', $titleIds)
            ->where('pref', true)
            ->count();

        if ($primaryCount !== 1) {
            throw ValidationException::withMessages([
                'titles' => ['A obra deve ter exatamente um título principal.'],
            ]);
        }
    }

    /**
     * Validation rules for updating a work, shared by the direct update
     * endpoint (VRACWorkController) and the work-suggestion accept flow.
     *
     * All relation fields are arrays of existing record UUIDs, applied via
     * sync() by ApplyWorkChangesAction.
     */
    public static function rules(): array
    {
        return [
            'location_id' => 'sometimes|nullable|uuid|exists:locations,id',

            'titles' => 'sometimes|array|min:1',
            'titles.*' => 'uuid|exists:vrac_titles,id',

            'agents' => 'sometimes|array',
            'agents.*' => 'uuid|exists:vrac_agents,id',

            'dates' => 'sometimes|array',
            'dates.*' => 'uuid|exists:vrac_dates,id',

            'materials' => 'sometimes|array',
            'materials.*' => 'uuid|exists:vrac_materials,id',

            'techniques' => 'sometimes|array',
            'techniques.*' => 'uuid|exists:vrac_techniques,id',

            'style_periods' => 'sometimes|array',
            'style_periods.*' => 'uuid|exists:vrac_style_periods,id',

            'cultural_contexts' => 'sometimes|array',
            'cultural_contexts.*' => 'uuid|exists:vrac_cultural_contexts,id',

            'work_types' => 'sometimes|array',
            'work_types.*' => 'uuid|exists:vrac_work_types,id',

            'subjects' => 'sometimes|array',
            'subjects.*' => 'uuid|exists:vrac_subjects,id',
        ];
    }
}
