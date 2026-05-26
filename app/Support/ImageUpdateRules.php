<?php

namespace App\Support;

use App\Models\VRACore\VRACContributorName;
use App\Models\VRACore\VRACSubject;
use App\Models\VRACore\VRACWork;
use Illuminate\Validation\Rule;

class ImageUpdateRules
{
    public static function rules(): array
    {
        return [

            // Campos base
            'user_id' => 'sometimes|uuid|exists:users,id',
            'collective_id' => 'nullable|uuid|exists:collectives,id',

            // Title
            'title' => 'sometimes|string|max:255',

            // Photographer
            'photographer' => [
                'sometimes',
                'uuid',
                Rule::exists(VRACContributorName::class, 'id'),
            ],

            // Description
            'description' => 'nullable|string|max:500',

            // Dates
            'earliest_date' => 'nullable|date_format:Y-m-d',
            'latest_date' => 'nullable|date_format:Y-m-d',
            'circa' => 'nullable|boolean',

            // Location
            'latitude' => 'nullable|decimal:1,8',
            'longitude' => 'nullable|decimal:1,8',
            'location_label' => 'nullable|string|max:255',

            // Subjects
            'subjects' => 'sometimes|array',
            'subjects.*' => [
                'uuid',
                Rule::exists(VRACSubject::class, 'id'),
            ],

            // Works
            'works' => 'sometimes|array',
            'works.*' => [
                'uuid',
                Rule::exists(VRACWork::class, 'id'),
            ],

            // IMPORTANTE: nunca permitir license en update
            'license' => ['prohibited'],
        ];
    }
}