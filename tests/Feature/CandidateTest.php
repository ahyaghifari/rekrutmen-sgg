<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateTest extends TestCase
{
    use RefreshDatabase;

    private function makeCandidate(array $overrides = []): Candidate
    {
        $user = User::factory()->create();

        return Candidate::create(array_merge([
            'user_id' => $user->id,
            'name' => $user->name,
        ], $overrides));
    }

    // ── PK & model config ────────────────────────────────────────────────────

    public function test_candidate_uses_ulid_primary_key(): void
    {
        $candidate = $this->makeCandidate();

        $this->assertIsString($candidate->id);
        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]{26}$/', $candidate->id);
    }

    public function test_one_user_can_only_have_one_candidate_profile(): void
    {
        $user = User::factory()->create();
        Candidate::create(['user_id' => $user->id, 'name' => 'A']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Candidate::create(['user_id' => $user->id, 'name' => 'B']);
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function test_candidate_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $candidate = Candidate::create([
            'user_id' => $user->id,
            'name' => $user->name,
        ]);

        $this->assertTrue($candidate->user->is($user));
    }

    public function test_candidate_has_many_job_applications(): void
    {
        $candidate = $this->makeCandidate();
        $opening = JobOpening::factory()->create();

        JobApplication::factory()->count(2)->create([
            'user_id' => $candidate->user_id,
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
        ]);

        $this->assertCount(2, $candidate->jobApplications);
    }

    // ── Latest job opening via applications ──────────────────────────────────

    public function test_latest_job_opening_resolved_through_applications(): void
    {
        $candidate = $this->makeCandidate();
        $first = JobOpening::factory()->create();
        $latest = JobOpening::factory()->create();

        JobApplication::factory()->create([
            'user_id' => $candidate->user_id,
            'candidate_id' => $candidate->id,
            'job_opening_id' => $first->id,
            'status' => 'rejected',
            'created_at' => now()->subDay(),
        ]);
        JobApplication::factory()->create([
            'user_id' => $candidate->user_id,
            'candidate_id' => $candidate->id,
            'job_opening_id' => $latest->id,
            'status' => 'applied',
            'created_at' => now(),
        ]);

        $resolved = $candidate->jobApplications()->latest()->first()->jobOpening;

        $this->assertTrue($resolved->is($latest));
    }

    // ── Soft delete ──────────────────────────────────────────────────────────

    public function test_candidate_is_soft_deleted(): void
    {
        $candidate = $this->makeCandidate();
        $id = $candidate->id;

        $candidate->delete();

        $this->assertNull(Candidate::find($id));
        $this->assertNotNull(Candidate::withTrashed()->find($id));
    }
}
