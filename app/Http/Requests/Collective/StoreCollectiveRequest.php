<?php

namespace App\Http\Requests\Collective;

use App\Models\VRACore\VRACSubject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:120',
            'email'           => 'nullable|email',
            'foundation_date' => 'nullable|date',
            'location'        => 'nullable|string|max:250',
            'avatar'          => 'nullable|image|mimes:jpg,jpeg,png|max:7168',
            'description'     => 'nullable|string',
            'socials'         => 'nullable|array',
            'subjects'        => ['nullable', 'array', Rule::exists(VRACSubject::class, 'id')],
        ];
    }
}
