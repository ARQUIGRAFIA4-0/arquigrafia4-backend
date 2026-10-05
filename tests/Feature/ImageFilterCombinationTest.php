<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACMaterial;
use App\Models\VRACore\VRACRight;
use App\Models\VRACore\VRACSubject;
use App\Models\VRACore\VRACTechnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImageFilterCombinationTest extends TestCase
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

    private function search(array $params)
    {
        return $this->getJson('/api/images?'.http_build_query($params));
    }

    private function ids($response)
    {
        return collect($response->json('data'))->pluck('id');
    }

    public function test_subject_filter_requires_every_selected_subject(): void
    {
        $user = $this->createUser();
        $concreto = VRACSubject::create(['term' => 'concreto']);
        $vidro = VRACSubject::create(['term' => 'vidro']);

        $both = VRACImage::create(['user_id' => $user->id]);
        $both->subjects()->sync([$concreto->id, $vidro->id]);

        $onlyOne = VRACImage::create(['user_id' => $user->id]);
        $onlyOne->subjects()->sync([$concreto->id]);

        $response = $this->search(['subject' => [$concreto->id, $vidro->id]]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $both->id);
    }

    public function test_subject_term_filter_requires_every_term(): void
    {
        $user = $this->createUser();

        $both = VRACImage::create(['user_id' => $user->id]);
        $both->subjects()->sync([
            VRACSubject::create(['term' => 'concreto aparente'])->id,
            VRACSubject::create(['term' => 'vidro temperado'])->id,
        ]);

        $onlyOne = VRACImage::create(['user_id' => $user->id]);
        $onlyOne->subjects()->sync([VRACSubject::create(['term' => 'concreto armado'])->id]);

        $response = $this->search(['subject_term' => ['concreto', 'vidro']]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $both->id);
    }

    public function test_material_filter_requires_every_selected_material_whether_linked_or_tagged(): void
    {
        $user = $this->createUser();
        $concreto = VRACMaterial::create(['label' => 'Concreto', 'vocab' => 'VCAA']);
        $vidro = VRACMaterial::create(['label' => 'Vidro', 'vocab' => 'VCAA']);

        $mixed = VRACImage::create(['user_id' => $user->id]);
        $mixed->materials()->sync([$concreto->id]);
        $mixed->subjects()->sync([VRACSubject::create(['term' => 'vidro'])->id]);

        $onlyConcreto = VRACImage::create(['user_id' => $user->id]);
        $onlyConcreto->materials()->sync([$concreto->id]);

        $response = $this->search(['material' => [$concreto->id, $vidro->id]]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $mixed->id);
    }

    public function test_technique_filter_requires_every_selected_technique(): void
    {
        $user = $this->createUser();
        $a = VRACTechnique::create(['label' => 'Fotografia digital', 'vocab' => 'VCAA']);
        $b = VRACTechnique::create(['label' => 'Desenho', 'vocab' => 'VCAA']);

        $both = VRACImage::create(['user_id' => $user->id]);
        $both->techniques()->sync([$a->id, $b->id]);

        $onlyA = VRACImage::create(['user_id' => $user->id]);
        $onlyA->techniques()->sync([$a->id]);

        $response = $this->search(['technique' => [$a->id, $b->id]]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $both->id);
    }

    public function test_license_filter_still_accepts_any_of_the_selected_licenses(): void
    {
        $user = $this->createUser();
        $by = VRACRight::create(['text' => 'CC BY 4.0', 'type' => 'license', 'rights_holder' => 'Autor', 'href' => 'https://creativecommons.org/licenses/by/4.0/']);
        $cc0 = VRACRight::create(['text' => 'CC0 1.0', 'type' => 'license', 'rights_holder' => 'Autor', 'href' => 'https://creativecommons.org/publicdomain/zero/1.0/']);
        $bync = VRACRight::create(['text' => 'CC BY-NC 4.0', 'type' => 'license', 'rights_holder' => 'Autor', 'href' => 'https://creativecommons.org/licenses/by-nc/4.0/']);

        $withBy = VRACImage::create(['user_id' => $user->id]);
        $withBy->rights()->sync([$by->id]);

        $withCc0 = VRACImage::create(['user_id' => $user->id]);
        $withCc0->rights()->sync([$cc0->id]);

        $withByNc = VRACImage::create(['user_id' => $user->id]);
        $withByNc->rights()->sync([$bync->id]);

        $response = $this->search(['license' => ['BY', 'CC0']]);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $ids = $this->ids($response);
        $this->assertTrue($ids->contains($withBy->id));
        $this->assertTrue($ids->contains($withCc0->id));
        $this->assertFalse($ids->contains($withByNc->id));
    }
}
