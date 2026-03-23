<?php

namespace App\Http\Requests\Collective;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('collective')->isAdmin($this->user());
    }

    public function rules(): array
    {
        return [
            'role' => 'required|string|in:admin,member',
        ];
    }
}
