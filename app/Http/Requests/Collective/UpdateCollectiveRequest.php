<?php

namespace App\Http\Requests\Collective;

use App\Models\VRACore\VRACSubject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('collective')->isAdmin($this->user());
    }

    public function rules(): array
    {
        return [
            'name'            => 'nullable|string|max:120',
            'email'           => 'nullable|email',
            'foundation_date' => 'nullable|date',
            'location'        => 'nullable|string|max:250',
            'avatar'          => 'nullable|file|mimes:jpg,jpeg,png|max:2000',
            'description'     => 'nullable|string',
            'socials'         => 'nullable|array',
            'subjects'        => ['nullable', 'array', Rule::exists(VRACSubject::class, 'id')],
        ];
    }
}
