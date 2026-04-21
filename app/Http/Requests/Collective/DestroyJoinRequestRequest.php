<?php

namespace App\Http\Requests\Collective;

use Illuminate\Foundation\Http\FormRequest;

/**
 * DestroyJoinRequestRequest
 *
 * Validates and authorizes cancellation of a join request.
 * Only the user who made the request can cancel it.
 */
class DestroyJoinRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only the user who made the join request can cancel it.
     */
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('user')->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [];
    }
}
