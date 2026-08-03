<?php

namespace Tests\Feature;

use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobApplicationTest extends TestCase
{
    use RefreshDatabase;

    // ── Business rule: 1 user = 1 lamaran aktif ──────────────────────────────

    public function test_has_active_application_returns_true_when_applied(): void
    {
        $user = User::factory()->create();
        JobApplication::factory()->create([
            'user_id' => $user->id,
            'status' => JobApplication::STATUS_APPLIED,
        ]);

        $this->assertTrue(JobApplication::hasActiveApplication($user->id));
    }

    public function test_has_active_application_returns_true_when_screening(): void
    {
        $user = User::factory()->create();
        JobApplication::factory()->create([
            'user_id' => $user->id,
            'status' => JobApplication::STATUS_SCREENING,
        ]);

        $this->assertTrue(JobApplication::hasActiveApplication($user->id));
    }

    public function test_has_active_application_returns_true_when_interview(): void
    {
        $user = User::factory()->create();
        JobApplication::factory()->create([
            'user_id' => $user->id,
            'status' => JobApplication::STATUS_INTERVIEW,
        ]);

        $this->assertTrue(JobApplication::hasActiveApplication($user->id));
    }

    public function test_has_active_application_returns_false_when_passed(): void
    {
        $user = User::factory()->create();
        JobApplication::factory()->create([
            'user_id' => $user->id,
            'status' => JobApplication::STATUS_PASSED,
        ]);

        $this->assertFalse(JobApplication::hasActiveApplication($user->id));
    }

    public function test_has_active_application_returns_false_when_rejected(): void
    {
        $user = User::factory()->create();
        JobApplication::factory()->create([
            'user_id' => $user->id,
            'status' => JobApplication::STATUS_REJECTED,
        ]);

        $this->assertFalse(JobApplication::hasActiveApplication($user->id));
    }

    public function test_has_active_application_returns_false_for_new_user(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(JobApplication::hasActiveApplication($user->id));
    }

    // ── Scope active ─────────────────────────────────────────────────────────

    public function test_active_scope_excludes_passed_and_rejected(): void
    {
        $user = User::factory()->create();

        JobApplication::factory()->create(['user_id' => $user->id, 'status' => 'applied']);
        JobApplication::factory()->create(['user_id' => $user->id, 'status' => 'passed']);
        JobApplication::factory()->create(['user_id' => $user->id, 'status' => 'rejected']);

        $this->assertCount(1, JobApplication::where('user_id', $user->id)->active()->get());
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function test_application_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $app = JobApplication::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($app->user->is($user));
    }

    public function test_application_belongs_to_job_opening(): void
    {
        $opening = JobOpening::factory()->create();
        $app = JobApplication::factory()->create(['job_opening_id' => $opening->id]);

        $this->assertTrue($app->jobOpening->is($opening));
    }

    // ── HTTP: form lamaran ────────────────────────────────────────────────────

    public function test_create_page_requires_auth(): void
    {
        $opening = JobOpening::factory()->create(['is_active' => true]);

        $this->get(route('lamaran.create', $opening))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_create_page(): void
    {
        $user = User::factory()->create();
        $opening = JobOpening::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('lamaran.create', $opening))
            ->assertOk();
    }

    public function test_user_with_active_application_cannot_access_create_page(): void
    {
        $user = User::factory()->create();
        $opening = JobOpening::factory()->create(['is_active' => true]);
        JobApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'applied',
        ]);

        $this->actingAs($user)
            ->get(route('lamaran.create', $opening))
            ->assertRedirect(route('dashboard'));
    }

    public function test_show_page_requires_auth(): void
    {
        $app = JobApplication::factory()->create();

        $this->get(route('lamaran.show', $app))
            ->assertRedirect(route('login'));
    }

    public function test_user_cannot_view_other_users_application(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $app = JobApplication::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('lamaran.show', $app))
            ->assertForbidden();
    }

    public function test_user_can_view_own_application(): void
    {
        $user = User::factory()->create();
        $app = JobApplication::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('lamaran.show', $app))
            ->assertOk();
    }
}
