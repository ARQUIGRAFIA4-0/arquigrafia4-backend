<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'image' => 'required|image|max:6000',
            'user_id' => 'required|uuid|exists:users',
            'collective_id' => 'nullable|uuid', // adicionar validação quando implementar collectives
            'legacy_id' => 'nullable|integer',
            'ref_id' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
        ];
    }
}
