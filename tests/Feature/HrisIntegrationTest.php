<?php

namespace Tests\Feature;

use App\Models\JobApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Endpoint integrasi HRIS — fokus updateApplicationStatus() (whitelist transisi + efek
 * samping JobApplication::booted).
 */
class HrisIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-hris-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.hris.api_token' => self::TOKEN]);
    }

    private function patchStatus(string $id, string $status)
    {
        return $this->withHeader('Authorization', 'Bearer ' . self::TOKEN)
            ->patchJson("/api/integrations/hris/applications/{$id}/status", ['status' => $status]);
    }

    public function test_rejects_request_without_token(): void
    {
        $app = JobApplication::factory()->create(['status' => 'screening']);

        $this->patchJson("/api/integrations/hris/applications/{$app->id}/status", ['status' => 'shortlisted'])
            ->assertStatus(401);
    }

    public function test_allowed_transition_updates_status_and_logs(): void
    {
        $app = JobApplication::factory()->create(['status' => 'screening']);

        $this->patchStatus($app->id, 'shortlisted')
            ->assertOk()
            ->assertJson(['from' => 'screening', 'to' => 'shortlisted', 'candidate_created' => false]);

        $this->assertSame('shortlisted', $app->fresh()->status);
        $this->assertDatabaseHas('job_application_status_logs', [
            'job_application_id' => $app->id,
            'from_status' => 'screening',
            'to_status' => 'shortlisted',
        ]);
    }

    public function test_disallowed_transition_is_rejected(): void
    {
        $app = JobApplication::factory()->create(['status' => 'applied']);

        $this->patchStatus($app->id, 'passed')->assertStatus(422);

        $this->assertSame('applied', $app->fresh()->status);
    }

    public function test_terminal_status_cannot_transition(): void
    {
        $app = JobApplication::factory()->create(['status' => 'passed']);

        $this->patchStatus($app->id, 'onboarding')->assertStatus(422);
    }

    public function test_unknown_application_returns_404(): void
    {
        $this->patchStatus('01JZZZZZZZZZZZZZZZZZZZZZZZZ', 'screening')->assertStatus(404);
    }

    public function test_transition_to_onboarding_creates_candidate_row(): void
    {
        $app = JobApplication::factory()->create(['status' => 'mcu']);

        $this->patchStatus($app->id, 'onboarding')
            ->assertOk()
            ->assertJson(['candidate_created' => true]);

        $this->assertDatabaseHas('candidates', ['user_id' => $app->user_id]);
    }
}
