<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACMaterial;
use App\Models\VRACore\VRACStylePeriod;
use App\Models\VRACore\VRACSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ImageVocabularySearchTest extends TestCase
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

    private function search(string $param, string $id)
    {
        return $this->getJson('/api/images?'.http_build_query([$param => [$id]]));
    }

    public function test_material_filter_matches_linked_images_and_images_tagged_with_the_same_text(): void
    {
        $user = $this->createUser();
        $material = VRACMaterial::create(['label' => 'Concreto', 'vocab' => 'VCAA']);
        $tag = VRACSubject::create(['term' => 'concreto']);

        $tagged = VRACImage::create(['user_id' => $user->id]);
        $tagged->subjects()->sync([$tag->id]);

        $linked = VRACImage::create(['user_id' => $user->id]);
        $linked->materials()->sync([$material->id]);

        $other = VRACImage::create(['user_id' => $user->id]);
        $other->subjects()->sync([VRACSubject::create(['term' => 'madeira'])->id]);

        $response = $this->search('material', $material->id);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($tagged->id));
        $this->assertTrue($ids->contains($linked->id));
        $this->assertFalse($ids->contains($other->id));
    }

    public function test_material_filter_works_with_either_duplicated_vocabulary_id(): void
    {
        $user = $this->createUser();
        $vcaa = VRACMaterial::create(['label' => 'vidro', 'vocab' => 'VCAA', 'ref_id' => '5236']);
        $manual = VRACMaterial::create(['label' => 'vidro', 'vocab' => 'ARQUIGRAFIA']);

        $image = VRACImage::create(['user_id' => $user->id]);
        $image->subjects()->sync([VRACSubject::create(['term' => 'Vidro'])->id]);

        foreach ([$vcaa, $manual] as $material) {
            $response = $this->search('material', $material->id);

            $response->assertStatus(200);
            $response->assertJsonCount(1, 'data');
            $response->assertJsonPath('data.0.id', $image->id);
        }
    }

    public function test_style_period_filter_matches_images_tagged_with_the_same_text(): void
    {
        $user = $this->createUser();
        $style = VRACStylePeriod::create(['label' => 'Modernismo', 'vocab' => 'VCAA']);

        $tagged = VRACImage::create(['user_id' => $user->id]);
        $tagged->subjects()->sync([VRACSubject::create(['term' => 'modernismo'])->id]);

        VRACImage::create(['user_id' => $user->id]);

        $response = $this->search('style_period', $style->id);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $tagged->id);
    }

    public function test_search_suggestions_are_built_from_tags_and_return_one_entry_per_label(): void
    {
        Cache::forget('image.search-suggestions');

        $user = $this->createUser();
        $vcaa = VRACMaterial::create(['label' => 'Vidro', 'vocab' => 'VCAA', 'ref_id' => '5236']);
        VRACMaterial::create(['label' => 'vidro', 'vocab' => 'ARQUIGRAFIA']);
        VRACMaterial::create(['label' => 'Madeira', 'vocab' => 'VCAA']);

        $tag = VRACSubject::create(['term' => 'vidro']);
        foreach (range(1, 2) as $i) {
            VRACImage::create(['user_id' => $user->id])->subjects()->sync([$tag->id]);
        }

        $deleted = VRACImage::create(['user_id' => $user->id]);
        $deleted->subjects()->sync([$tag->id]);
        $deleted->delete();

        $response = $this->getJson('/api/images/search-suggestions');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'materials');
        $response->assertJsonPath('materials.0.id', $vcaa->id);
        $response->assertJsonPath('materials.0.total', 2);

        Cache::forget('image.search-suggestions');
    }
}
