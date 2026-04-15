<?php

namespace Tests\Feature;

use App\Models\Collective;
use App\Models\CollectiveJoinRequest;
use App\Models\User;
use App\Models\VRACore\VRACImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CollectiveTest extends TestCase
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

    private function createCollectiveWithAdmin(User $admin): Collective
    {
        $collective = Collective::create([
            'name' => fake()->company(),
            'bio' => fake()->sentence(),
        ]);
        $collective->members()->attach($admin->id, ['role' => 'admin']);
        return $collective;
    }

    // --- Collective CRUD ---

    public function test_user_can_create_collective(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $response = $this->postJson('/api/collectives', [
            'name' => 'Test Collective',
            'bio' => 'A test bio',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Test Collective');

        $collective = Collective::first();
        $this->assertTrue($collective->isAdmin($user));
    }

    public function test_public_can_view_collective(): void
    {
        $admin = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);

        $response = $this->getJson("/api/collectives/{$collective->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', $collective->name);
    }

    public function test_public_can_list_collectives(): void
    {
        $admin = $this->createUser();
        $this->createCollectiveWithAdmin($admin);

        $response = $this->getJson('/api/collectives');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_admin_can_update_collective(): void
    {
        $admin = $this->createUser();
        Passport::actingAs($admin);
        $collective = $this->createCollectiveWithAdmin($admin);

        $response = $this->putJson("/api/collectives/{$collective->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_non_admin_cannot_update_collective(): void
    {
        $admin = $this->createUser();
        $member = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);
        $collective->members()->attach($member->id, ['role' => 'member']);

        Passport::actingAs($member);

        $response = $this->putJson("/api/collectives/{$collective->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_collective(): void
    {
        $admin = $this->createUser();
        Passport::actingAs($admin);
        $collective = $this->createCollectiveWithAdmin($admin);

        $response = $this->deleteJson("/api/collectives/{$collective->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('collectives', ['id' => $collective->id]);
    }

    public function test_non_admin_cannot_delete_collective(): void
    {
        $admin = $this->createUser();
        $member = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);
        $collective->members()->attach($member->id, ['role' => 'member']);

        Passport::actingAs($member);

        $response = $this->deleteJson("/api/collectives/{$collective->id}");

        $response->assertStatus(403);
    }

    // --- Members ---

    public function test_admin_can_promote_member(): void
    {
        $admin = $this->createUser();
        $member = $this->createUser();
        Passport::actingAs($admin);
        $collective = $this->createCollectiveWithAdmin($admin);
        $collective->members()->attach($member->id, ['role' => 'member']);

        $response = $this->putJson("/api/collectives/{$collective->id}/members/{$member->id}", [
            'role' => 'admin',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($collective->fresh()->isAdmin($member));
    }

    public function test_cannot_demote_last_admin(): void
    {
        $admin = $this->createUser();
        Passport::actingAs($admin);
        $collective = $this->createCollectiveWithAdmin($admin);

        $response = $this->putJson("/api/collectives/{$collective->id}/members/{$admin->id}", [
            'role' => 'member',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_remove_member(): void
    {
        $admin = $this->createUser();
        $member = $this->createUser();
        Passport::actingAs($admin);
        $collective = $this->createCollectiveWithAdmin($admin);
        $collective->members()->attach($member->id, ['role' => 'member']);

        $response = $this->deleteJson("/api/collectives/{$collective->id}/members/{$member->id}");

        $response->assertStatus(200);
        $this->assertFalse($collective->fresh()->isMember($member));
    }

    public function test_member_can_leave(): void
    {
        $admin = $this->createUser();
        $member = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);
        $collective->members()->attach($member->id, ['role' => 'member']);

        Passport::actingAs($member);

        $response = $this->deleteJson("/api/collectives/{$collective->id}/members/{$member->id}");

        $response->assertStatus(200);
        $this->assertFalse($collective->fresh()->isMember($member));
    }

    public function test_member_cannot_remove_other_member(): void
    {
        $admin = $this->createUser();
        $member1 = $this->createUser();
        $member2 = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);
        $collective->members()->attach($member1->id, ['role' => 'member']);
        $collective->members()->attach($member2->id, ['role' => 'member']);

        Passport::actingAs($member1);

        $response = $this->deleteJson("/api/collectives/{$collective->id}/members/{$member2->id}");

        $response->assertStatus(403);
    }

    public function test_cannot_remove_last_admin(): void
    {
        $admin = $this->createUser();
        Passport::actingAs($admin);
        $collective = $this->createCollectiveWithAdmin($admin);

        $response = $this->deleteJson("/api/collectives/{$collective->id}/members/{$admin->id}");

        $response->assertStatus(422);
    }

    // --- Join Requests ---

    public function test_authenticated_user_can_request_to_join(): void
    {
        $admin    = $this->createUser();
        $outsider = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);

        Passport::actingAs($outsider);

        $response = $this->postJson("/api/collectives/{$collective->id}/join-requests");

        $response->assertStatus(201);
        $this->assertDatabaseHas('collective_join_requests', [
            'collective_id' => $collective->id,
            'user_id'       => $outsider->id,
            'status'        => 'pending',
        ]);
    }

    public function test_member_cannot_request_to_join(): void
    {
        $admin  = $this->createUser();
        $member = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);
        $collective->members()->attach($member->id, ['role' => 'member']);

        Passport::actingAs($member);

        $response = $this->postJson("/api/collectives/{$collective->id}/join-requests");

        $response->assertStatus(422);
    }

    public function test_duplicate_pending_request_is_rejected(): void
    {
        $admin    = $this->createUser();
        $outsider = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);

        CollectiveJoinRequest::create([
            'collective_id' => $collective->id,
            'user_id'       => $outsider->id,
            'status'        => 'pending',
        ]);

        Passport::actingAs($outsider);

        $response = $this->postJson("/api/collectives/{$collective->id}/join-requests");

        $response->assertStatus(422);
    }

    public function test_admin_can_list_pending_join_requests(): void
    {
        $admin    = $this->createUser();
        $outsider = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);

        CollectiveJoinRequest::create([
            'collective_id' => $collective->id,
            'user_id'       => $outsider->id,
            'status'        => 'pending',
        ]);

        Passport::actingAs($admin);

        $response = $this->getJson("/api/collectives/{$collective->id}/join-requests");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_non_admin_cannot_list_join_requests(): void
    {
        $admin  = $this->createUser();
        $member = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);
        $collective->members()->attach($member->id, ['role' => 'member']);

        Passport::actingAs($member);

        $response = $this->getJson("/api/collectives/{$collective->id}/join-requests");

        $response->assertStatus(403);
    }

    public function test_admin_can_approve_join_request(): void
    {
        $admin    = $this->createUser();
        $outsider = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);

        CollectiveJoinRequest::create([
            'collective_id' => $collective->id,
            'user_id'       => $outsider->id,
            'status'        => 'pending',
        ]);

        Passport::actingAs($admin);

        $response = $this->putJson("/api/collectives/{$collective->id}/join-requests/{$outsider->id}", [
            'action' => 'approve',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($collective->fresh()->isMember($outsider));
        $this->assertDatabaseHas('collective_join_requests', [
            'collective_id' => $collective->id,
            'user_id'       => $outsider->id,
            'status'        => 'approved',
        ]);
    }

    public function test_admin_can_reject_join_request(): void
    {
        $admin    = $this->createUser();
        $outsider = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);

        CollectiveJoinRequest::create([
            'collective_id' => $collective->id,
            'user_id'       => $outsider->id,
            'status'        => 'pending',
        ]);

        Passport::actingAs($admin);

        $response = $this->putJson("/api/collectives/{$collective->id}/join-requests/{$outsider->id}", [
            'action' => 'reject',
        ]);

        $response->assertStatus(200);
        $this->assertFalse($collective->fresh()->isMember($outsider));
        $this->assertDatabaseHas('collective_join_requests', [
            'collective_id' => $collective->id,
            'user_id'       => $outsider->id,
            'status'        => 'rejected',
        ]);
    }

    public function test_non_admin_cannot_approve_or_reject_join_request(): void
    {
        $admin    = $this->createUser();
        $member   = $this->createUser();
        $outsider = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);
        $collective->members()->attach($member->id, ['role' => 'member']);

        CollectiveJoinRequest::create([
            'collective_id' => $collective->id,
            'user_id'       => $outsider->id,
            'status'        => 'pending',
        ]);

        Passport::actingAs($member);

        $response = $this->putJson("/api/collectives/{$collective->id}/join-requests/{$outsider->id}", [
            'action' => 'approve',
        ]);

        $response->assertStatus(403);
    }

    public function test_requester_can_cancel_their_pending_request(): void
    {
        $admin    = $this->createUser();
        $outsider = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);

        CollectiveJoinRequest::create([
            'collective_id' => $collective->id,
            'user_id'       => $outsider->id,
            'status'        => 'pending',
        ]);

        Passport::actingAs($outsider);

        $response = $this->deleteJson("/api/collectives/{$collective->id}/join-requests/{$outsider->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('collective_join_requests', [
            'collective_id' => $collective->id,
            'user_id'       => $outsider->id,
        ]);
    }

    public function test_user_cannot_cancel_another_users_request(): void
    {
        $admin    = $this->createUser();
        $outsider = $this->createUser();
        $other    = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);

        CollectiveJoinRequest::create([
            'collective_id' => $collective->id,
            'user_id'       => $outsider->id,
            'status'        => 'pending',
        ]);

        Passport::actingAs($other);

        $response = $this->deleteJson("/api/collectives/{$collective->id}/join-requests/{$outsider->id}");

        $response->assertStatus(403);
    }

    // --- Collective Images ---

    public function test_collective_image_hides_uploader(): void
    {
        $admin = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);

        $image = VRACImage::create([
            'user_id' => $admin->id,
            'collective_id' => $collective->id,
        ]);

        $response = $this->getJson("/api/images/{$image->id}");

        $response->assertStatus(200);
        $response->assertJsonMissing(['user_id' => $admin->id]);
        $response->assertJsonStructure(['data' => ['collective']]);
    }

    public function test_non_member_cannot_upload_for_collective(): void
    {
        $admin = $this->createUser();
        $outsider = $this->createUser();
        $collective = $this->createCollectiveWithAdmin($admin);

        Passport::actingAs($outsider);

        $response = $this->postJson('/api/images', [
            'user_id' => $outsider->id,
            'collective_id' => $collective->id,
            'title' => 'Test',
            'license' => 'CC-BY',
            'photographer' => Str::uuid(),
        ]);

        $response->assertStatus(403);
    }
}
