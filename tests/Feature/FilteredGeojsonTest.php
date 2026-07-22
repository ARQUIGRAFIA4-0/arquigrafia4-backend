<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACTitle;
use App\Models\VRACore\VRACWork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FilteredGeojsonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // The endpoint caches per filter-string; isolate each test from cached collections.
        Cache::flush();
    }

    private function createUser(): User
    {
        return User::create([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ]);
    }

    private function createTitle(string $label): VRACTitle
    {
        $title = new VRACTitle;
        $title->label = $label;
        $title->pref = true;
        $title->save();

        return $title;
    }

    private function createLocation(float $lat = -23.5505, float $lng = -46.6333): Location
    {
        return Location::create([
            'latitude' => $lat,
            'longitude' => $lng,
            'label' => 'São Paulo',
        ]);
    }

    /** Create a located image, optionally attached to a work. */
    private function locatedImage(User $user, ?VRACWork $work = null): VRACImage
    {
        $image = VRACImage::create(['user_id' => $user->id]);
        $image->locations()->sync([$this->createLocation()->id]);
        if ($work) {
            $image->works()->sync([$work->id]);
        }

        return $image;
    }

    public function test_returns_feature_collection_of_located_images(): void
    {
        $user = $this->createUser();
        $this->locatedImage($user);
        $this->locatedImage($user);
        // An image with no location must not appear.
        VRACImage::create(['user_id' => $user->id]);

        $response = $this->getJson('/api/locations/geojson/search');

        $response->assertStatus(200);
        $response->assertJsonPath('type', 'FeatureCollection');
        $response->assertJsonCount(2, 'features');
    }

    public function test_filters_image_features_by_work_id(): void
    {
        $user = $this->createUser();
        $work = VRACWork::create(['location_id' => $this->createLocation()->id]);
        $work->titles()->sync([$this->createTitle('Edifício Copan')->id]);

        $matching = $this->locatedImage($user, $work);
        $this->locatedImage($user); // unrelated image, must be filtered out

        $response = $this->getJson("/api/locations/geojson/search?work[]={$work->id}");

        $response->assertStatus(200);

        $imageFeatures = collect($response->json('features'))
            ->where('properties.feature_type', 'image');

        $this->assertCount(1, $imageFeatures);
        $this->assertSame($matching->id, $imageFeatures->first()['properties']['image_id']);
    }

    public function test_works_layer_stays_in_sync_with_image_filters(): void
    {
        $user = $this->createUser();

        $copan = VRACWork::create(['location_id' => $this->createLocation()->id]);
        $copan->titles()->sync([$this->createTitle('Edifício Copan')->id]);
        $this->locatedImage($user, $copan);

        // A work whose images do NOT match the filter must be dropped from the map.
        $other = VRACWork::create(['location_id' => $this->createLocation()->id]);
        $other->titles()->sync([$this->createTitle('Outro')->id]);
        $this->locatedImage($user, $other);

        $response = $this->getJson("/api/locations/geojson/search?work[]={$copan->id}");

        $response->assertStatus(200);

        $workFeatures = collect($response->json('features'))
            ->where('properties.feature_type', 'work');

        $this->assertCount(1, $workFeatures);
        $this->assertSame($copan->id, $workFeatures->first()['properties']['work_id']);
    }

    public function test_works_only_returns_only_work_features(): void
    {
        $user = $this->createUser();
        $work = VRACWork::create(['location_id' => $this->createLocation()->id]);
        $work->titles()->sync([$this->createTitle('Edifício Copan')->id]);
        $this->locatedImage($user, $work);

        $response = $this->getJson('/api/locations/geojson/search?works_only=true');

        $response->assertStatus(200);

        $types = collect($response->json('features'))->pluck('properties.feature_type')->unique();

        $this->assertEquals(['work'], $types->values()->all());
    }
}
