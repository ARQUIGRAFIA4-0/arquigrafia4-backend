<?php

namespace App\Http\Requests\Binomial;

use Illuminate\Foundation\Http\FormRequest;

class StoreBinomialEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evaluations'               => ['required', 'array', 'min:1'],
            'evaluations.*.binomial_id' => ['required', 'integer', 'exists:binomials,id'],
            'evaluations.*.value'       => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }
}
