<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VRACore\VRACImage;
use App\Models\VRACore\VRACTitle;
use App\Models\VRACore\VRACWork;
use App\Models\WorkSuggestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class WorkSuggestionTest extends TestCase
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

    private function createTitle(string $label = 'Antigo'): VRACTitle
    {
        $title = new VRACTitle;
        $title->label = $label;
        $title->pref = true;
        $title->save();

        return $title;
    }

    /**
     * A work with one image owned by $owner.
     */
    private function workOwnedBy(User $owner): VRACWork
    {
        $work = VRACWork::create();
        $image = VRACImage::create(['user_id' => $owner->id]);
        $image->works()->sync([$work->id]);

        return $work;
    }

    public function test_authenticated_user_can_create_work_suggestion(): void
    {
        $suggester = $this->createUser();
        $work = $this->workOwnedBy($this->createUser());

        Passport::actingAs($suggester);

        $response = $this->postJson("/api/vrac-works/{$work->id}/suggestions", [
            'payload' => ['titles' => [$this->createTitle('Novo')->id]],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('work_suggestions', [
            'work_id' => $work->id,
            'user_id' => $suggester->id,
            'status' => 'pending',
        ]);
    }

    public function test_owner_of_related_image_can_accept_suggestion(): void
    {
        $owner = $this->createUser();
        $work = $this->workOwnedBy($owner);
        $newTitle = $this->createTitle('Novo');

        $suggestion = WorkSuggestion::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'work_id' => $work->id,
            'user_id' => $this->createUser()->id,
            'status' => 'pending',
            'payload' => ['titles' => [$newTitle->id]],
        ]);

        Passport::actingAs($owner);

        $response = $this->postJson("/api/work-suggestions/{$suggestion->id}/accept");

        $response->assertStatus(200);
        $this->assertEquals('accepted', $suggestion->fresh()->status);
        $this->assertEquals($newTitle->id, $work->fresh()->titles()->first()->id);
    }

    public function test_non_owner_cannot_accept_suggestion(): void
    {
        $work = $this->workOwnedBy($this->createUser());
        $stranger = $this->createUser();

        $suggestion = WorkSuggestion::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'work_id' => $work->id,
            'user_id' => $this->createUser()->id,
            'status' => 'pending',
            'payload' => ['titles' => [$this->createTitle('Novo')->id]],
        ]);

        Passport::actingAs($stranger);

        $response = $this->postJson("/api/work-suggestions/{$suggestion->id}/accept");

        $response->assertStatus(403);
        $this->assertEquals('pending', $suggestion->fresh()->status);
    }

    public function test_author_cannot_accept_own_suggestion_even_owning_related_image(): void
    {
        $author = $this->createUser();
        $work = $this->workOwnedBy($author);

        $suggestion = WorkSuggestion::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'work_id' => $work->id,
            'user_id' => $author->id,
            'status' => 'pending',
            'payload' => ['titles' => [$this->createTitle('Novo')->id]],
        ]);

        Passport::actingAs($author);

        $response = $this->postJson("/api/work-suggestions/{$suggestion->id}/accept");

        $response->assertStatus(403);
        $this->assertEquals('pending', $suggestion->fresh()->status);
    }

    public function test_author_cannot_reject_own_suggestion(): void
    {
        $author = $this->createUser();
        $work = $this->workOwnedBy($author);

        $suggestion = WorkSuggestion::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'work_id' => $work->id,
            'user_id' => $author->id,
            'status' => 'pending',
            'payload' => ['titles' => [$this->createTitle('Novo')->id]],
        ]);

        Passport::actingAs($author);

        $response = $this->postJson("/api/work-suggestions/{$suggestion->id}/reject");

        $response->assertStatus(403);
        $this->assertEquals('pending', $suggestion->fresh()->status);
    }

    public function test_owner_of_related_image_can_reject_suggestion(): void
    {
        $owner = $this->createUser();
        $work = $this->workOwnedBy($owner);

        $suggestion = WorkSuggestion::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'work_id' => $work->id,
            'user_id' => $this->createUser()->id,
            'status' => 'pending',
            'payload' => ['titles' => [$this->createTitle('Novo')->id]],
        ]);

        Passport::actingAs($owner);

        $response = $this->postJson("/api/work-suggestions/{$suggestion->id}/reject", [
            'review_note' => 'Não procede',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('rejected', $suggestion->fresh()->status);
    }

    public function test_partial_accept_only_applies_selected_fields(): void
    {
        $owner = $this->createUser();
        $work = $this->workOwnedBy($owner);
        $acceptedTitle = $this->createTitle('Aceito');
        $ignoredSubject = null; // subjects not applied; only titles accepted

        $suggestion = WorkSuggestion::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'work_id' => $work->id,
            'user_id' => $this->createUser()->id,
            'status' => 'pending',
            'payload' => [
                'titles' => [$acceptedTitle->id],
                'agents' => [],
            ],
        ]);

        Passport::actingAs($owner);

        $response = $this->postJson("/api/work-suggestions/{$suggestion->id}/accept", [
            'accepted_fields' => ['titles'],
        ]);

        $response->assertStatus(200);
        $this->assertEquals('partially_accepted', $suggestion->fresh()->status);
        $this->assertEquals($acceptedTitle->id, $work->fresh()->titles()->first()->id);
    }

    public function test_only_author_can_update_pending_suggestion(): void
    {
        $work = $this->workOwnedBy($this->createUser());
        $author = $this->createUser();

        $suggestion = WorkSuggestion::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'work_id' => $work->id,
            'user_id' => $author->id,
            'status' => 'pending',
            'payload' => ['titles' => [$this->createTitle()->id]],
        ]);

        Passport::actingAs($this->createUser());

        $response = $this->putJson("/api/work-suggestions/{$suggestion->id}", [
            'payload' => ['titles' => [$this->createTitle('X')->id]],
        ]);

        $response->assertStatus(403);
    }

    public function test_reviewed_suggestion_cannot_be_accepted_again(): void
    {
        $owner = $this->createUser();
        $work = $this->workOwnedBy($owner);

        $suggestion = WorkSuggestion::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'work_id' => $work->id,
            'user_id' => $this->createUser()->id,
            'status' => 'accepted',
            'payload' => ['titles' => [$this->createTitle()->id]],
        ]);

        Passport::actingAs($owner);

        $response = $this->postJson("/api/work-suggestions/{$suggestion->id}/accept");

        $response->assertStatus(422);
    }

    public function test_public_can_list_and_show_work_suggestions(): void
    {
        $work = $this->workOwnedBy($this->createUser());

        WorkSuggestion::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'work_id' => $work->id,
            'user_id' => $this->createUser()->id,
            'status' => 'pending',
            'payload' => ['titles' => [$this->createTitle()->id]],
        ]);

        $this->getJson('/api/work-suggestions')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
