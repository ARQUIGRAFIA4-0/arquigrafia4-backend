<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Collective;
use App\Models\Percurso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlbumSearchTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ]);
    }

    private function album(string $title, ?User $user = null, ?Collective $collective = null, bool $private = false): Album
    {
        return Album::create([
            'title' => $title,
            'user_id' => $collective ? null : ($user ?? $this->user(fake()->name()))->id,
            'collective_id' => $collective?->id,
            'is_private' => $private,
        ]);
    }

    private function search(array $params = [])
    {
        return $this->getJson('/api/albums?'.http_build_query($params));
    }

    private function titles($response): array
    {
        return collect($response->json('data'))->pluck('title')->all();
    }

    public function test_lists_only_public_albums_and_includes_the_percursos_count(): void
    {
        $withPercursos = $this->album('Publico com percursos');
        Percurso::create(['album_id' => $withPercursos->id]);
        Percurso::create(['album_id' => $withPercursos->id]);
        $this->album('Privado', null, null, true);

        $response = $this->search();

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Publico com percursos');
        $response->assertJsonPath('data.0.percursos_count', 2);
    }

    public function test_title_filter_needs_every_word_in_any_order_ignoring_case_and_accents(): void
    {
        $this->album('Obras de Gabriela Santos');
        $this->album('Fotos Gabriéla');
        $this->album('Outro álbum');

        $this->assertCount(2, $this->titles($this->search(['title' => 'gabriela'])));
        $this->assertSame(['Obras de Gabriela Santos'], $this->titles($this->search(['title' => 'santos GABRIELA'])));
    }

    public function test_an_exact_title_comes_before_partial_matches(): void
    {
        $this->album('Gabriela');
        $this->album('Fotos de Gabriela');
        $this->album('Gabriela no centro');

        $titles = $this->titles($this->search(['title' => 'gabriela']));

        $this->assertSame('Gabriela', $titles[0]);
        $this->assertCount(3, $titles);
    }

    public function test_user_filter_matches_the_owner_name(): void
    {
        $this->album('Do Carlos', $this->user('Carlos Lima'));
        $this->album('Da Gabriela', $this->user('Gabriela Souza'));

        $this->assertSame(['Da Gabriela'], $this->titles($this->search(['user' => 'gabriela'])));
    }

    public function test_collective_filter_matches_the_owner_name(): void
    {
        $this->album('Do Coletivo FAU', null, Collective::create(['name' => 'Coletivo FAU']));
        $this->album('Do Coletivo Rua', null, Collective::create(['name' => 'Coletivo Rua']));

        $this->assertSame(['Do Coletivo FAU'], $this->titles($this->search(['collective' => 'fau'])));
    }

    public function test_has_percursos_filters_in_both_directions(): void
    {
        $with = $this->album('Com percurso');
        Percurso::create(['album_id' => $with->id]);
        $this->album('Sem percurso');

        $this->assertSame(['Com percurso'], $this->titles($this->search(['has_percursos' => 'true'])));
        $this->assertSame(['Sem percurso'], $this->titles($this->search(['has_percursos' => 'false'])));
        $this->assertCount(2, $this->titles($this->search()));
    }

    public function test_filters_are_combined_with_and(): void
    {
        foreach (['Gabriela 1', 'Gabriela 2', 'Gabriela 3'] as $title) {
            $album = $this->album($title);
            if ($title === 'Gabriela 2') {
                Percurso::create(['album_id' => $album->id]);
            }
        }
        $this->album('Outro tema');

        $this->assertCount(3, $this->titles($this->search(['title' => 'gabriela'])));
        $this->assertSame(['Gabriela 2'], $this->titles($this->search(['title' => 'gabriela', 'has_percursos' => 'true'])));

        $response = $this->search(['user' => 'a', 'collective' => 'a']);
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    public function test_pagination_links_keep_the_filters(): void
    {
        $this->album('Gabriela 1');
        $this->album('Gabriela 2');

        $response = $this->search(['title' => 'gabriela', 'per_page' => 1]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertStringContainsString('title=gabriela', $response->json('next_page_url'));
    }

    public function test_an_invalid_has_percursos_value_is_rejected(): void
    {
        $this->search(['has_percursos' => 'talvez'])->assertStatus(422);
    }
}
