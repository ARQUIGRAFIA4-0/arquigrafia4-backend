<?php

namespace App\Http\Requests\Collective;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateJoinRequestRequest
 *
 * Validates and authorizes approval or rejection of a join request.
 * Only collective admins can approve or reject join requests.
 */
class UpdateJoinRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only collective admins can approve or reject join requests.
     */
    public function authorize(): bool
    {
        return $this->route('collective')->isAdmin($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'action' => 'required|string|in:approve,reject',
        ];
    }
}
