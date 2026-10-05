<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACSubject;
use App\Models\VRACore\VRACTitle;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * InnoDB FULLTEXT indexes only see committed rows, so these tests cannot run inside the
 * transaction used by RefreshDatabase. DatabaseTruncation commits and truncates afterwards.
 */
class ImageTextSearchTest extends TestCase
{
    use DatabaseTruncation;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ]);
    }

    private function image(string $title, array $subjects = []): VRACImage
    {
        $image = VRACImage::create(['user_id' => $this->user->id]);
        $image->titles()->sync([VRACTitle::create(['label' => $title, 'type' => 'other'])->id]);

        if ($subjects !== []) {
            $image->subjects()->sync(
                collect($subjects)->map(fn (string $term) => VRACSubject::create(['term' => $term])->id)->all()
            );
        }

        return $image;
    }

    private function search(string $q)
    {
        return $this->getJson('/api/images?'.http_build_query(['q' => $q]));
    }

    public function test_every_word_must_match(): void
    {
        $match = $this->image('Edifício Dom Pedro');
        $this->image('Edifício Copan');
        $this->image('Palácio Dom Pedro');

        $response = $this->search('edifício dom pedro');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_words_may_match_in_different_fields(): void
    {
        $match = $this->image('Mosaico azul', ['cerâmica']);
        $this->image('Mosaico verde');

        $response = $this->search('mosaico cerâmica');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_words_match_as_prefixes(): void
    {
        $match = $this->image('Mosaicos azuis');

        $response = $this->search('mosaico');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_connectors_and_short_words_are_ignored(): void
    {
        $match = $this->image('Casa de Vidro');

        $response = $this->search('casa de vidro');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_search_operators_do_not_change_the_meaning_or_break_the_query(): void
    {
        $match = $this->image('Edifício Dom Pedro');
        $this->image('Edifício Copan');

        $response = $this->search('"edifício" -dom (pedro*');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_two_letter_words_are_required_as_whole_words(): void
    {
        $match = $this->image('Praça da Sé');
        $this->image('Praça Roosevelt');
        $this->image('Praça Marechal');

        $response = $this->search('praça da se');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_a_two_letter_word_can_match_in_the_address(): void
    {
        $match = $this->image('Catedral');
        $match->locations()->sync([
            Location::create(['latitude' => -23.55, 'longitude' => -46.63, 'label' => 'Praça da Sé, São Paulo, SP'])->id,
        ]);
        $this->image('Catedral Metropolitana');

        $response = $this->search('catedral sé');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_a_search_of_only_a_two_letter_word_matches_whole_words_only(): void
    {
        $match = $this->image('Praça da Sé');
        $this->image('Seminário Roosevelt');

        $response = $this->search('sé');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_a_search_made_only_of_ignored_words_does_not_fail(): void
    {
        $this->image('Casa de Vidro');

        $this->search('de da')->assertStatus(200);
    }

    public function test_images_with_every_word_in_the_title_come_first(): void
    {
        $titleMatch = $this->image('Edifício Dom Pedro');
        $tagMatch = $this->image('Praça central', ['dom pedro']);

        $response = $this->search('dom pedro');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$titleMatch->id, $tagMatch->id], $ids);
    }
}
