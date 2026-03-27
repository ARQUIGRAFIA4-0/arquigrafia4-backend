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
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'dimensions:max_width=2000,max_height=2000', 'max:5120']
        ];
    }
}
