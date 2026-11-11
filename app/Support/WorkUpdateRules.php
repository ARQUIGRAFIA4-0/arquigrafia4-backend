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
     * Top-level keys accepted in a work update / suggestion payload.
     *
     * @return array<int, string>
     */
    public static function allowedKeys(): array
    {
        return array_values(array_filter(
            array_keys(self::rules()),
            fn (string $key) => ! str_contains($key, '.')
        ));
    }

    /**
     * Validation rules for a work-suggestion payload nested under $prefix.
     * Same rules as a direct update, plus: only known keys are allowed.
     * `reason` (the author's free-text justification) is accepted but is not
     * a work field and is never applied. Pair with assertPayloadHasWorkField().
     */
    public static function payloadRules(string $prefix = 'payload'): array
    {
        $workFields = self::allowedKeys();

        $rules = [
            $prefix => ['required', 'array:'.implode(',', [...$workFields, 'reason'])],
            "{$prefix}.reason" => 'sometimes|nullable|string|max:2000',
        ];

        foreach (self::rules() as $key => $rule) {
            $rules["{$prefix}.{$key}"] = $rule;
        }

        return $rules;
    }

    /**
     * Require at least one work field in the payload (`reason` alone would
     * change nothing). Throws a ValidationException keyed on $prefix.
     */
    public static function assertPayloadHasWorkField(array $payload, string $prefix = 'payload'): void
    {
        if (array_intersect(array_keys($payload), self::allowedKeys()) === []) {
            throw ValidationException::withMessages([
                $prefix => ['A sugestão deve alterar ao menos um campo da obra.'],
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

            'descriptions' => 'sometimes|array',
            'descriptions.*' => 'uuid|exists:vrac_descriptions,id',

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
