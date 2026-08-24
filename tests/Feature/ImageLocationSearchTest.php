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
