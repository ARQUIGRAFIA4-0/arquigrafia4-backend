<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use App\Models\VRACore\VRACImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImageLocationSearchTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::create([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ]);
    }

    private function createLocation(string $label): Location
    {
        return Location::create([
            'latitude' => -23.5505,
            'longitude' => -46.6333,
            'label' => $label,
        ]);
    }

    public function test_location_param_filters_images_by_location_label(): void
    {
        $user = $this->createUser();

        $matching = VRACImage::create(['user_id' => $user->id]);
        $matching->locations()->sync([$this->createLocation('São Paulo, SP')->id]);

        $other = VRACImage::create(['user_id' => $user->id]);
        $other->locations()->sync([$this->createLocation('Rio de Janeiro, RJ')->id]);

        $response = $this->getJson('/api/images?location=São Paulo');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $matching->id);
    }

    /**
     * @param  array<int, string>  $labels
     * @return array<string, string> image id by label
     */
    private function createImagesWithLocations(array $labels): array
    {
        $user = $this->createUser();
        $ids = [];

        foreach ($labels as $label) {
            $image = VRACImage::create(['user_id' => $user->id]);
            $image->locations()->sync([$this->createLocation($label)->id]);
            $ids[$label] = $image->id;
        }

        return $ids;
    }

    /**
     * @return array<int, string>
     */
    private function locationSearchIds(string $location): array
    {
        $response = $this->getJson('/api/images?per_page=50&location='.urlencode($location));
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        sort($ids);

        return $ids;
    }

    /**
     * @param  array<int, string>  $ids
     * @return array<int, string>
     */
    private function sorted(array $ids): array
    {
        sort($ids);

        return $ids;
    }

    public function test_state_abbreviation_matches_whole_word_and_state_name(): void
    {
        $ids = $this->createImagesWithLocations([
            'Avenida Monsenhor Tabosa, 3055 — Centro, Itapipoca - CE',
            'Praça do Ferreira, Fortaleza, Ceará, Brasil',
            'Rua da Aurora — Centro, Recife - PE',
            'Rua Conceição, Vila Mariana, São Paulo - SP',
        ]);

        $ceara = $this->sorted([
            $ids['Avenida Monsenhor Tabosa, 3055 — Centro, Itapipoca - CE'],
            $ids['Praça do Ferreira, Fortaleza, Ceará, Brasil'],
        ]);

        $this->assertSame($ceara, $this->locationSearchIds('CE'));
        $this->assertSame($ceara, $this->locationSearchIds('ceará'));
    }

    public function test_every_word_must_match_the_same_label_in_any_order(): void
    {
        $ids = $this->createImagesWithLocations([
            'Rua Major Facundo — Centro, Fortaleza - CE',
            'Rua Fortaleza — Centro, Curitiba - PR',
            'Rua Floriano Peixoto — Centro, Sobral - CE',
        ]);

        $fortalezaCe = [$ids['Rua Major Facundo — Centro, Fortaleza - CE']];

        $this->assertSame($fortalezaCe, $this->locationSearchIds('Fortaleza CE'));
        $this->assertSame($fortalezaCe, $this->locationSearchIds('CE Fortal'));
    }

    public function test_multi_word_state_names_and_connector_like_abbreviations(): void
    {
        $ids = $this->createImagesWithLocations([
            'Campo Grande, Mato Grosso do Sul, Brasil',
            'Cuiabá - MT',
            'Palmas - TO',
        ]);

        $this->assertSame([$ids['Campo Grande, Mato Grosso do Sul, Brasil']], $this->locationSearchIds('Mato Grosso do Sul'));
        $this->assertSame([$ids['Cuiabá - MT']], $this->locationSearchIds('Mato Grosso'));
        $this->assertSame([$ids['Palmas - TO']], $this->locationSearchIds('TO'));
    }

    public function test_full_text_search_matches_location_label(): void
    {
        $user = $this->createUser();

        $matching = VRACImage::create(['user_id' => $user->id]);
        $matching->locations()->sync([$this->createLocation('Copacabana')->id]);

        $other = VRACImage::create(['user_id' => $user->id]);
        $other->locations()->sync([$this->createLocation('Ipanema')->id]);

        $response = $this->getJson('/api/images?q=Copacabana');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $matching->id);
    }
}
