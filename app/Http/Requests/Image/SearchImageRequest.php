<?php

namespace App\Http\Requests\Image;

use Illuminate\Foundation\Http\FormRequest;

class SearchImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function queryParameters(): array
    {
        return [
            'q' => ['description' => 'Busca full-text em títulos, assuntos, descrições e nomes de contribuidores.', 'example' => 'No-example'],
            'title' => ['description' => 'Filtra por título da imagem (busca parcial).', 'example' => 'No-example'],
            'contributor' => ['description' => 'Filtra por nome do contribuidor (busca parcial).', 'example' => 'No-example'],
            'subject' => ['description' => 'Filtra por UUIDs de assuntos.', 'type' => 'string[]', 'example' => 'No-example'],
            'subject.*' => ['description' => '', 'example' => null],
            'subject_term' => ['description' => 'Filtra por termos de assunto (busca parcial).', 'type' => 'string[]', 'example' => 'No-example'],
            'subject_term.*' => ['description' => '', 'example' => null],
            'date_from' => ['description' => 'Filtra imagens com data mais antiga >= este valor (formato: AAAA-MM-DD).', 'example' => '1900-01-01'],
            'date_to' => ['description' => 'Filtra imagens com data mais recente <= este valor (formato: AAAA-MM-DD).', 'example' => '1910-12-31'],
            'user_id' => ['description' => 'Filtra imagens enviadas por um usuário específico (UUID).', 'example' => 'No-example'],
            'collective_id' => ['description' => 'Filtra por coletivo. Envie um UUID para um coletivo específico, ou envie sem valor (`?collective_id=`) para retornar apenas imagens pessoais.', 'example' => 'No-example'],
            'license' => ['description' => 'Filtra por licença(s) Creative Commons. Para múltiplas licenças, repita o parâmetro: `?license[]=BY&license[]=CC0`.', 'type' => 'string[]', 'example' => 'No-example'],
            'license.*' => ['description' => 'Uma licença Creative Commons. Para múltiplas licenças, repita o parâmetro: `?license[]=BY&license[]=CC0`.', 'example' => 'BY'],
            'sort_by' => ['description' => 'Campo de ordenação: `created_at`, `title` ou `date`. Padrão: ordem aleatória.', 'example' => 'created_at'],
            'sort_order' => ['description' => 'Direção da ordenação: `asc` ou `desc`.', 'example' => 'desc'],
            'per_page' => ['description' => 'Resultados por página (mín: 1, máx: 100, padrão: 50).', 'example' => 5],
        ];
    }

    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'contributor' => 'nullable|string|max:255',
            'subject' => 'nullable|array',
            'subject.*' => 'uuid',
            'subject_term' => 'nullable|array',
            'subject_term.*' => 'string|max:255',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
            'user_id' => 'nullable|uuid',
            'collective_id' => 'sometimes|nullable|uuid',
            'license' => 'nullable|array',
            'license.*' => 'string|in:BY,BY-SA,BY-ND,BY-NC,BY-NC-SA,BY-NC-ND,CC0',
            'sort_by' => 'nullable|string|in:created_at,title,date',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
