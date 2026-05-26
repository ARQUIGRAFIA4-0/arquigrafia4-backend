<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACTitle;
use App\Models\VRACore\VRACWork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class VRACWorkTest extends TestCase
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

    private function createTitle(string $label = 'Edifício Copan', bool $pref = true): VRACTitle
    {
        $title = new VRACTitle();
        $title->label = $label;
        $title->pref = $pref;
        $title->save();
        return $title;
    }

    private function createLocation(float $lat = -23.5505, float $lng = -46.6333, string $label = 'São Paulo'): Location
    {
        return Location::create([
            'latitude' => $lat,
            'longitude' => $lng,
            'label' => $label,
        ]);
    }

    public function test_public_can_list_works(): void
    {
        $location = $this->createLocation();
        $work = VRACWork::create(['location_id' => $location->id]);
        $work->titles()->sync([$this->createTitle()->id]);

        $response = $this->getJson('/api/vrac-works');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_per_page_minus_one_returns_all(): void
    {
        $location = $this->createLocation();
        for ($i = 0; $i < 20; $i++) {
            VRACWork::create(['location_id' => $location->id]);
        }

        $response = $this->getJson('/api/vrac-works?per_page=-1');

        $response->assertStatus(200);
        $response->assertJsonCount(20, 'data');
    }

    public function test_public_can_show_work_with_relations(): void
    {
        $location = $this->createLocation();
        $work = VRACWork::create(['location_id' => $location->id]);
        $title = $this->createTitle('Edifício Martinelli');
        $work->titles()->sync([$title->id]);

        $response = $this->getJson("/api/vrac-works/{$work->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $work->id);
        $response->assertJsonPath('data.titles.0.label', 'Edifício Martinelli');
        $response->assertJsonPath('data.location.id', $location->id);
    }

    public function test_bbox_filter_includes_works_inside_box(): void
    {
        $inside = $this->createLocation(-23.55, -46.63);
        $outside = $this->createLocation(40.7, -74.0);

        VRACWork::create(['location_id' => $inside->id]);
        VRACWork::create(['location_id' => $outside->id]);

        $response = $this->getJson('/api/vrac-works?bbox=-46.7,-23.6,-46.5,-23.5');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_unauthenticated_user_cannot_create_work(): void
    {
        $title = $this->createTitle();
        $location = $this->createLocation();

        $response = $this->postJson('/api/vrac-works', [
            'location_id' => $location->id,
            'titles' => [$title->id],
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_work_with_existing_location(): void
    {
        Passport::actingAs($this->createUser());
        $title = $this->createTitle();
        $location = $this->createLocation();

        $response = $this->postJson('/api/vrac-works', [
            'location_id' => $location->id,
            'titles' => [$title->id],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.location.id', $location->id);
        $response->assertJsonPath('data.titles.0.id', $title->id);
    }

    public function test_authenticated_user_can_create_work_with_new_location(): void
    {
        Passport::actingAs($this->createUser());
        $title = $this->createTitle();

        $response = $this->postJson('/api/vrac-works', [
            'latitude' => -23.5505,
            'longitude' => -46.6333,
            'location_label' => 'Praça da Sé',
            'titles' => [$title->id],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.location.label', 'Praça da Sé');
        $this->assertDatabaseHas('locations', ['label' => 'Praça da Sé']);
    }

    public function test_creating_work_requires_at_least_one_title(): void
    {
        Passport::actingAs($this->createUser());
        $location = $this->createLocation();

        $response = $this->postJson('/api/vrac-works', [
            'location_id' => $location->id,
            'titles' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['titles']);
    }

    public function test_creating_work_requires_location(): void
    {
        Passport::actingAs($this->createUser());
        $title = $this->createTitle();

        $response = $this->postJson('/api/vrac-works', [
            'titles' => [$title->id],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_update_syncs_titles(): void
    {
        Passport::actingAs($this->createUser());
        $location = $this->createLocation();
        $work = VRACWork::create(['location_id' => $location->id]);
        $work->titles()->sync([$this->createTitle('Antigo')->id]);

        $newTitle = $this->createTitle('Novo');

        $response = $this->putJson("/api/vrac-works/{$work->id}", [
            'titles' => [$newTitle->id],
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $work->fresh()->titles()->count());
        $this->assertEquals($newTitle->id, $work->fresh()->titles()->first()->id);
    }

    public function test_destroy_soft_deletes_work(): void
    {
        Passport::actingAs($this->createUser());
        $location = $this->createLocation();
        $work = VRACWork::create(['location_id' => $location->id]);

        $response = $this->deleteJson("/api/vrac-works/{$work->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('vrac_works', ['id' => $work->id]);
    }

    public function test_image_can_be_attached_to_works(): void
    {
        $location = $this->createLocation();
        $work = VRACWork::create(['location_id' => $location->id]);

        $image = VRACImage::create([
            'user_id' => $this->createUser()->id,
        ]);
        $image->works()->sync([$work->id]);

        $this->assertEquals(1, $image->fresh()->works()->count());
        $this->assertEquals(1, $work->fresh()->images()->count());
    }

    public function test_image_search_filters_by_work_id(): void
    {
        $user = $this->createUser();
        $location = $this->createLocation();
        $work = VRACWork::create(['location_id' => $location->id]);

        $imageWithWork = VRACImage::create(['user_id' => $user->id]);
        $imageWithWork->works()->sync([$work->id]);

        VRACImage::create(['user_id' => $user->id]);

        $response = $this->getJson("/api/images?work[]={$work->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $imageWithWork->id);
    }
}
