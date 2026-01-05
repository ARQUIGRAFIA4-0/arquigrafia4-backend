<?php

namespace App\Http\Requests\Image;

use App\Models\VRACore\VRACContributorName;
use App\Models\VRACore\VRACSubject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user()->id != $this->input('user_id')) return false;

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
            // 'image' => 'required|file|mimes:jpg,jpeg,png,heic|max:6000',
            'user_id' => 'required|uuid|exists:users,id',
            'collective_id' => 'nullable|uuid', // adicionar validação quando implementar collectives
            'photographer' => [
                'required',
                'uuid',
                Rule::exists(VRACContributorName::class, 'id'),
            ],
            'owner_name' => 'required|string|max:255',
            'right_text' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'earliest_date' => 'nullable|date_format:Y-m-d',
            'latest_date' => 'nullable|date_format:Y-m-d',
            'circa' => 'nullable|boolean',
            'latitude' => 'nullable|decimal:1,8',
            'longitude' => 'nullable|decimal:1,8',
            'location_label' => 'nullable|string|max:255',
            'subjects' => [
                'nullable',
                'array',
                Rule::exists(VRACSubject::class, 'id'),
            ],
        ];
    }
}
