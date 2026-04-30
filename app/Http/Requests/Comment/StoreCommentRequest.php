<?php

namespace App\Http\Requests\Comment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
        // return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image_id'  => ['required', 'uuid', 'exists:vrac_images,id'],
            'content'   => ['required', 'string', 'min:1', 'max:2000'],
            'parent_id' => [
                'nullable',
                'uuid',
                'exists:comments,id',
                // garante que o pai pertence à mesma imagem
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $parent = \App\Models\Comment::find($value);
                        if ($parent && $parent->image_id !== $this->image_id) {
                            $fail('O comentário pai não pertence a esta imagem.');
                        }
                    }
                },
            ],
        ];
    }
}
