<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user()->id != $this->input('user_id')) return false;
        if ($this->user()->id != optional($this->profile)->user_id) return false;

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
            'phone' => 'nullable|string|max:20',
            'scholarity' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:250',
            'socials' => 'nullable|array',
            'configurations' => 'nullable|array',
            'country' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:50',
        ];
    }
}
