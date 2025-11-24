<?php

namespace App\Http\Requests\Profile;

use App\Models\VRACore\VRACSubject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user()->id != $this->input('user_id')) return false;

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|uuid|exists:users,id',
            'gender' => 'nullable|string|max:20',
            'birthdate' => 'nullable|date',
            'scholarity' => 'nullable|string|max:20',
            'socials' => 'nullable|array',
            'configurations' => 'nullable|array',
            'bio' => 'nullable|string|max:500',
            'race' => 'nullable|string|max:20',
            'profession' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:250',
            'subjects' => [
                'nullable',
                'array',
                Rule::exists(VRACSubject::class, 'id'),
            ],
        ];
    }
}
