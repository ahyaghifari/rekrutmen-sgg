<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\InterviewInvitee;
use App\Models\InterviewSchedule;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/** DB_CONNECTION=mysql DB_DATABASE=rekrutmen_test php artisan test --filter=ScheduleResponseTest */
class ScheduleResponseTest extends TestCase
{
    use DatabaseTransactions;

    private function invitee(string $scheduleStatus = 'published'): array
    {
        $co = Company::create(['name' => 'PT Uji ' . Str::random(4), 'hris_code' => strtoupper(Str::random(5))]);
        $opening = JobOpening::create(['id' => (string) Str::ulid(), 'company_id' => $co->id, 'company_name' => $co->name, 'title' => 'X', 'quota' => 1, 'is_active' => true]);
        $user = User::factory()->create();
        $app = JobApplication::create(['user_id' => $user->id, 'job_opening_id' => $opening->id, 'status' => 'interview']);
        $sch = InterviewSchedule::create(['job_opening_id' => $opening->id, 'interview_date' => now()->addDays(3)->toDateString(), 'status' => $scheduleStatus]);

        return [$user, InterviewInvitee::create(['interview_schedule_id' => $sch->id, 'job_application_id' => $app->id])];
    }

    private function url(InterviewInvitee $i): string
    {
        return route('jadwal.respons', ['wawancara', $i->id]);
    }

    public function test_attend_saved_and_second_answer_rejected(): void
    {
        [$user, $inv] = $this->invitee();
        $this->actingAs($user)->post($this->url($inv), ['response' => 'attend'])->assertSessionHas('success');
        $this->actingAs($user)->post($this->url($inv), ['response' => 'absent_with_reason', 'response_reason' => 'x'])->assertSessionHas('warning');

        $inv->refresh();
        $this->assertSame('attend', $inv->response);
        $this->assertNotNull($inv->responded_at);
    }

    public function test_reason_required_and_reschedule_needs_future_datetime(): void
    {
        [$user, $inv] = $this->invitee();
        $this->actingAs($user)->post($this->url($inv), ['response' => 'absent_with_reason'])->assertSessionHasErrors('response_reason');
        $this->actingAs($user)->post($this->url($inv), ['response' => 'reschedule', 'response_reason' => 'x', 'proposed_date' => now()->toDateString(), 'proposed_time' => '09:00'])
            ->assertSessionHasErrors('proposed_date');

        $this->actingAs($user)->post($this->url($inv), ['response' => 'reschedule', 'response_reason' => 'bentrok', 'proposed_date' => now()->addDays(5)->toDateString(), 'proposed_time' => '09:00'])
            ->assertSessionHasNoErrors();
        $inv->refresh();
        $this->assertSame('pending', $inv->reschedule_status);
        $this->assertSame('09:00:00', $inv->proposed_time);
    }

    public function test_other_user_and_draft_schedule_cannot_respond(): void
    {
        [, $inv] = $this->invitee();
        $this->actingAs(User::factory()->create())->post($this->url($inv), ['response' => 'attend'])->assertForbidden();

        [$user, $draft] = $this->invitee('draft');
        $this->actingAs($user)->post($this->url($draft), ['response' => 'attend'])->assertSessionHas('warning');
        $this->assertNull($draft->fresh()->responded_at);
    }

    public function test_dashboard_shows_form_then_readonly_answer(): void
    {
        [$user, $inv] = $this->invitee();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Konfirmasi Kehadiran')->assertSee('Kirim Jawaban')
            ->assertSee('perlu disetujui terlebih dahulu oleh sistem');

        $this->post($this->url($inv), ['response' => 'absent_with_reason', 'response_reason' => 'sakit']);
        $this->get(route('dashboard'))->assertOk()->assertSee('Tidak hadir dengan alasan')->assertSee('sakit')->assertDontSee('Kirim Jawaban');
    }

    public function test_answered_moves_to_compact_summary_with_reschedule_status(): void
    {
        [$user, $inv] = $this->invitee();
        $inv->update(['response' => 'reschedule', 'response_reason' => 'bentrok', 'responded_at' => now(), 'proposed_date' => now()->addDays(4)->toDateString(), 'proposed_time' => '13:30', 'reschedule_status' => 'pending']);

        $this->actingAs($user)->get(route('dashboard'))->assertDontSee('Konfirmasi Kehadiran')
            ->assertSee('Jawaban Anda')->assertSee('13:30 WITA')->assertSee('Menunggu persetujuan');

        $inv->update(['reschedule_status' => 'approved']);
        $this->get(route('dashboard'))->assertSee('Disetujui');
        $inv->update(['reschedule_status' => 'rejected']);
        $this->get(route('dashboard'))->assertSee('Tidak disetujui');
    }

    public function test_email_sent_when_hr_approves_or_rejects_reschedule(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        [$user, $inv] = $this->invitee();
        $inv->update(['response' => 'reschedule', 'response_reason' => 'x', 'responded_at' => now(), 'proposed_date' => now()->addDays(5)->toDateString(), 'proposed_time' => '13:30', 'reschedule_status' => 'pending']);

        $inv->update(['reschedule_status' => 'approved']);
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\ApplicantNotificationMail::class, fn ($m) => $m->hasTo($user->email)
            && str_contains($m->notification->subject, 'Disetujui') && str_contains($m->notification->body, '13:30 WITA'));

        // Ditolak -> jadwal awal tetap; tidak terkirim dua kali untuk event yang sama.
        [$user2, $inv2] = $this->invitee();
        $inv2->update(['response' => 'reschedule', 'response_reason' => 'x', 'responded_at' => now(), 'proposed_date' => now()->addDays(5)->toDateString(), 'proposed_time' => '13:30', 'reschedule_status' => 'pending']);
        $inv2->update(['reschedule_status' => 'rejected', 'reschedule_note' => 'Dokter penguji tidak tersedia']);
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\ApplicantNotificationMail::class, fn ($m) => $m->hasTo($user2->email)
            && str_contains($m->notification->subject, 'Tidak Disetujui') && str_contains($m->notification->body, 'jadwal awal')
            && str_contains($m->notification->body, 'Dokter penguji tidak tersedia') && ! str_contains($m->notification->body, '13:30'));
    }

    public function test_no_email_when_response_is_not_a_pending_decision(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        [, $inv] = $this->invitee();
        $inv->update(['response' => 'attend', 'responded_at' => now()]);
        $inv->update(['reschedule_note' => 'x']);

        \Illuminate\Support\Facades\Mail::assertNothingQueued();
    }
}
