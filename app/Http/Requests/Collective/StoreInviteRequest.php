<?php

namespace App\Http\Requests\Collective;

use Illuminate\Foundation\Http\FormRequest;

class StoreInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('collective')->isAdmin($this->user());
    }

    public function rules(): array
    {
        return [
            'is_single_use' => 'required|boolean',
            'max_uses' => 'nullable|integer|min:1|required_if:is_single_use,false',
            'expires_at' => 'nullable|date|after:now',
        ];
    }
}
