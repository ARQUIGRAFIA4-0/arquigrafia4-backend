<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        if ($this->user()->id != $this->route('user')->id) return false;

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
            'name' => 'required|string|max:60',
            'email' => [
                'required',
                'email',
                'max:250',
                Rule::unique('users')->ignore($this->user()->id),
            ],
            'password' => ['nullable', 'max:250', Password::min(8)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:7168']
        ];
    }

    public function messages()
    {
        return [
            'avatar.image' => 'O arquivo deve ser uma imagem válida.',
            'avatar.mimes' => 'A imagem deve estar no formato: JPG, JPEG ou PNG.',
            'avatar.max' => 'A imagem deve ter no máximo 7MB.',
        ];
    }
}
