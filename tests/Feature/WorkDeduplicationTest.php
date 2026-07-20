<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use App\Models\VRACore\VRACTitle;
use App\Models\VRACore\VRACWork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class WorkDeduplicationTest extends TestCase
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

    private function title(string $label, bool $pref = true): VRACTitle
    {
        $title = new VRACTitle;
        $title->label = $label;
        $title->pref = $pref;
        $title->save();

        return $title;
    }

    private function location(float $lat, float $lng): Location
    {
        $location = new Location;
        $location->latitude = $lat;
        $location->longitude = $lng;
        $location->save();

        return $location;
    }

    /**
     * An existing work with $label at ($lat, $lng).
     */
    private function existingWork(string $label, float $lat, float $lng): VRACWork
    {
        $work = VRACWork::create(['location_id' => $this->location($lat, $lng)->id]);
        $work->titles()->sync([$this->title($label)->id]);

        return $work;
    }

    public function test_creating_duplicate_within_radius_is_rejected(): void
    {
        Passport::actingAs($this->createUser());

        $existing = $this->existingWork('Igreja da Sé', -23.5500, -46.6333);

        // ~30 m away, same primary name.
        $response = $this->postJson('/api/vrac-works', [
            'latitude' => -23.5502,
            'longitude' => -46.6334,
            'titles' => [$this->title('Igreja da Sé')->id],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('existing_work.id', $existing->id);
        $this->assertEquals(1, VRACWork::count());
    }

    public function test_same_name_outside_radius_is_allowed(): void
    {
        Passport::actingAs($this->createUser());

        $this->existingWork('Igreja da Sé', -23.5500, -46.6333);

        // ~1.5 km away — a genuinely different work sharing the name.
        $response = $this->postJson('/api/vrac-works', [
            'latitude' => -23.5600,
            'longitude' => -46.6400,
            'titles' => [$this->title('Igreja da Sé')->id],
        ]);

        $response->assertStatus(201);
        $this->assertEquals(2, VRACWork::count());
    }

    public function test_different_name_same_location_is_allowed(): void
    {
        Passport::actingAs($this->createUser());

        $this->existingWork('Igreja da Sé', -23.5500, -46.6333);

        $response = $this->postJson('/api/vrac-works', [
            'latitude' => -23.5500,
            'longitude' => -46.6333,
            'titles' => [$this->title('Edifício Martinelli')->id],
        ]);

        $response->assertStatus(201);
        $this->assertEquals(2, VRACWork::count());
    }

    public function test_store_requires_exactly_one_primary_title(): void
    {
        Passport::actingAs($this->createUser());

        // Two pref=true titles → rejected.
        $response = $this->postJson('/api/vrac-works', [
            'latitude' => -23.55,
            'longitude' => -46.63,
            'titles' => [
                $this->title('Principal A')->id,
                $this->title('Principal B')->id,
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('titles');
        $this->assertEquals(0, VRACWork::count());
    }

    public function test_store_allows_one_primary_plus_alternates(): void
    {
        Passport::actingAs($this->createUser());

        $response = $this->postJson('/api/vrac-works', [
            'latitude' => -23.55,
            'longitude' => -46.63,
            'titles' => [
                $this->title('Principal')->id,
                $this->title('Alternativo', pref: false)->id,
            ],
        ]);

        $response->assertStatus(201);
        $this->assertEquals(1, VRACWork::count());
    }

    public function test_update_that_would_duplicate_is_rejected(): void
    {
        Passport::actingAs($this->createUser());

        $existing = $this->existingWork('Teatro Municipal', -23.5450, -46.6380);

        // A second work nearby with a different name.
        $other = VRACWork::create(['location_id' => $this->location(-23.5451, -46.6381)->id]);
        $other->titles()->sync([$this->title('Outro')->id]);

        // Rename $other to collide with $existing at ~15 m.
        $response = $this->putJson("/api/vrac-works/{$other->id}", [
            'titles' => [$this->title('Teatro Municipal')->id],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('existing_work.id', $existing->id);
    }

    public function test_updating_a_work_does_not_flag_itself(): void
    {
        Passport::actingAs($this->createUser());

        $work = $this->existingWork('Pinacoteca', -23.5340, -46.6330);

        // Re-submit the same primary title; should not collide with itself.
        $response = $this->putJson("/api/vrac-works/{$work->id}", [
            'titles' => [$this->title('Pinacoteca')->id],
        ]);

        $response->assertStatus(200);
    }
}
