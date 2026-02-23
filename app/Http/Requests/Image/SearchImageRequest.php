<?php

namespace App\Http\Requests\Image;

use Illuminate\Foundation\Http\FormRequest;

class SearchImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'contributor' => 'nullable|string|max:255',
            'subject' => 'nullable|array',
            'subject.*' => 'uuid',
            'subject_term' => 'nullable|string|max:255',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
            'user_id' => 'nullable|uuid',
            'sort_by' => 'nullable|string|in:created_at,title,date',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
