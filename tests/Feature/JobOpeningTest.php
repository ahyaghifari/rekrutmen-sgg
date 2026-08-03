<?php

namespace Tests\Feature;

use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobOpeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_opening_uses_ulid_primary_key(): void
    {
        $opening = JobOpening::factory()->create();

        $this->assertIsString($opening->id);
        $this->assertMatchesRegularExpression('/^[0-9A-Z]{26}$/', $opening->id);
    }

    public function test_scope_active_returns_only_active_openings(): void
    {
        JobOpening::factory()->create(['is_active' => true]);
        JobOpening::factory()->create(['is_active' => true]);
        JobOpening::factory()->create(['is_active' => false]);

        $this->assertCount(2, JobOpening::active()->get());
    }

    public function test_inactive_opening_is_excluded_from_active_scope(): void
    {
        $inactive = JobOpening::factory()->create(['is_active' => false]);

        $this->assertFalse(JobOpening::active()->where('id', $inactive->id)->exists());
    }

    public function test_job_opening_has_many_applications(): void
    {
        $opening = JobOpening::factory()->create();
        JobApplication::factory()->count(3)->create(['job_opening_id' => $opening->id]);

        $this->assertCount(3, $opening->applications);
    }

    public function test_job_opening_cannot_be_deleted_while_has_applications(): void
    {
        $opening = JobOpening::factory()->create();
        JobApplication::factory()->create(['job_opening_id' => $opening->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $opening->forceDelete();
    }

    public function test_detail_page_is_accessible_without_login(): void
    {
        $opening = JobOpening::factory()->create(['is_active' => true]);

        $this->get(route('lowongan.show', $opening))
            ->assertOk();
    }

    public function test_inactive_opening_detail_returns_404(): void
    {
        $opening = JobOpening::factory()->create(['is_active' => false]);

        $this->get(route('lowongan.show', $opening))
            ->assertNotFound();
    }
}
