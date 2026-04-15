<?php

namespace App\Http\Requests\Collective;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreJoinRequestRequest
 *
 * Validates and authorizes a user's request to join a collective.
 * The auth:api middleware ensures only authenticated users can reach this endpoint.
 * Business logic in the controller checks if the user is already a member or has a pending request.
 */
class StoreJoinRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The auth:api middleware already ensures authentication, so we just return true here.
     * Membership validation happens in the controller to provide specific 422 responses.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [];
    }
}
