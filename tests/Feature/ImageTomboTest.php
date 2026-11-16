<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VRACore\VRACImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ImageTomboTest extends TestCase
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

    public function test_image_detail_exposes_the_tombo(): void
    {
        $image = VRACImage::create([
            'user_id' => $this->createUser()->id,
            'tombo' => '9A8148',
        ]);

        $response = $this->getJson("/api/images/{$image->id}");

        $response->assertStatus(200)->assertJsonPath('data.tombo', '9A8148');
    }

    public function test_owner_can_update_the_tombo(): void
    {
        $owner = $this->createUser();
        $image = VRACImage::create(['user_id' => $owner->id]);

        Passport::actingAs($owner);

        $response = $this->putJson("/api/images/{$image->id}", ['tombo' => 'FAU-123']);

        $response->assertStatus(200)->assertJsonPath('data.tombo', 'FAU-123');
        $this->assertDatabaseHas('vrac_images', ['id' => $image->id, 'tombo' => 'FAU-123']);
    }

    public function test_non_owner_cannot_update_the_tombo(): void
    {
        $image = VRACImage::create([
            'user_id' => $this->createUser()->id,
            'tombo' => 'ORIGINAL',
        ]);

        Passport::actingAs($this->createUser());

        $response = $this->putJson("/api/images/{$image->id}", ['tombo' => 'OUTRO']);

        $response->assertStatus(403);
        $this->assertDatabaseHas('vrac_images', ['id' => $image->id, 'tombo' => 'ORIGINAL']);
    }

    public function test_tombo_longer_than_64_characters_is_rejected(): void
    {
        $owner = $this->createUser();
        $image = VRACImage::create(['user_id' => $owner->id]);

        Passport::actingAs($owner);

        $response = $this->putJson("/api/images/{$image->id}", ['tombo' => str_repeat('a', 65)]);

        $response->assertStatus(422)->assertJsonValidationErrors(['tombo']);
    }

    public function test_null_clears_the_tombo(): void
    {
        $owner = $this->createUser();
        $image = VRACImage::create(['user_id' => $owner->id, 'tombo' => 'APAGAR']);

        Passport::actingAs($owner);

        $response = $this->putJson("/api/images/{$image->id}", ['tombo' => null]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('vrac_images', ['id' => $image->id, 'tombo' => null]);
    }
}
