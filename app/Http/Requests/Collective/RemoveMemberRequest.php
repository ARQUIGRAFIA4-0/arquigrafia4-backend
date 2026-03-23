<?php

namespace App\Http\Requests\Collective;

use Illuminate\Foundation\Http\FormRequest;

class RemoveMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $collective = $this->route('collective');
        $targetUser = $this->route('user');

        // Admin can remove anyone, or user can remove self
        return $collective->isAdmin($this->user())
            || $this->user()->id === $targetUser->id;
    }

    public function rules(): array
    {
        return [];
    }
}
