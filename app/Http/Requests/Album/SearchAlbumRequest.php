<?php

namespace App\Http\Requests\Album;

use Illuminate\Foundation\Http\FormRequest;

class SearchAlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function queryParameters(): array
    {
        return [
            'title' => ['description' => 'Filtra pelo título do álbum (busca parcial, sem diferenciar maiúsculas nem acentos). Com várias palavras, todas precisam aparecer. Título exatamente igual ao texto vem primeiro.', 'example' => 'No-example'],
            'user' => ['description' => 'Filtra pelo nome do usuário dono do álbum (busca parcial). Nome exatamente igual ao texto vem primeiro.', 'example' => 'No-example'],
            'collective' => ['description' => 'Filtra pelo nome do coletivo dono do álbum (busca parcial). Nome exatamente igual ao texto vem primeiro. Um álbum é de um usuário ou de um coletivo, então combinar `user` e `collective` não retorna nada.', 'example' => 'No-example'],
            'has_percursos' => ['description' => 'Com `true`, apenas álbuns com pelo menos um percurso; com `false`, apenas álbuns sem nenhum.', 'example' => 'No-example'],
            'per_page' => ['description' => 'Resultados por página (mín: 1, máx: 100, padrão: 15).', 'example' => 15],
        ];
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'user' => 'nullable|string|max:255',
            'collective' => 'nullable|string|max:255',
            'has_percursos' => 'nullable|in:true,false,1,0',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
