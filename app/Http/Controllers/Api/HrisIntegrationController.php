<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ApplicantNotificationMail;
use App\Models\ApplicationNotification;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Endpoint integrasi HRIS. HRIS mengelola seluruh proses rekrutmen langsung ke DB bersama
 * (koneksi `recruitment`); satu-satunya hal yang lewat HTTP ke sini adalah email pemberitahuan
 * pelamar — HRIS minta project ini menyusun + mengirim emailnya lewat queue-nya sendiri.
 */
class HrisIntegrationController extends Controller
{
    /** Event pemberitahuan yang boleh diminta HRIS. */
    private const NOTIFICATION_EVENTS = [
        'screening_rejected', 'screening_passed', 'quiz_sent', 'quiz_passed', 'quiz_rejected',
        'interview_scheduled', 'interview_passed', 'interview_rejected',
        'mcu_scheduled', 'mcu_passed', 'mcu_rejected',
        'onboarding_invite', 'offering_letter_ready', 'manual_rejected',
        'interview_rescheduled', 'mcu_rescheduled', 'offering_negotiation_replied',
        'offering_signed_received', 'offering_signed_accepted', 'offering_revision_requested',
        'offline_test_scheduled', 'offline_test_rescheduled',
    ];

    /**
     * Event yang boleh dikirim berulang (tidak dedup by job_application_id+event). HRIS
     * mengirim subject+body verbatim untuk semua event di sini.
     */
    private const REPEATABLE_EVENTS = [
        'interview_rescheduled', 'mcu_rescheduled', 'offering_negotiation_replied',
        'offering_signed_accepted', 'offering_revision_requested', 'offline_test_rescheduled',
    ];

    /**
     * HRIS minta project ini mengirim SATU email pemberitahuan pelamar sesuai `event`
     * (lolos berkas s/d onboarding). Project ini yang menyusun email (template + default),
     * lalu kirim lewat queue-nya sendiri (Mail ShouldQueue -> queue:work).
     *
     * Body: job_application_id, event, extra?{}, subject?, body?, hold?(bool).
     *  - subject+body dikirim verbatim (untuk event tanpa template: reschedule/negosiasi).
     *  - hold=true: simpan draft, JANGAN kirim (mis. penolakan seleksi berkas yang ditahan
     *    sampai admin klik "Kirim Email Pemberitahuan" -> panggil /notifications/flush).
     */
    public function sendNotification(Request $request): JsonResponse
    {
        $data = $request->validate([
            'job_application_id' => ['required', 'string'],
            'event'             => ['required', Rule::in(self::NOTIFICATION_EVENTS)],
            'extra'             => ['sometimes', 'array'],
            'subject'           => ['sometimes', 'string'],
            'body'              => ['sometimes', 'string'],
            'hold'              => ['sometimes', 'boolean'],
        ]);

        $log = Log::channel('hris');
        $log->info('sendNotification masuk.', [
            'job_application_id' => $data['job_application_id'],
            'event' => $data['event'],
            'hold' => $data['hold'] ?? false,
            'verbatim' => isset($data['subject'], $data['body']),
        ]);

        $app = JobApplication::with(['user', 'jobOpening'])->find($data['job_application_id']);

        if (! $app) {
            $log->warning('Lamaran tidak ditemukan.', ['job_application_id' => $data['job_application_id']]);

            return response()->json(['message' => 'Lamaran tidak ditemukan.'], 404);
        }

        $email = $app->user?->email;

        if (! $email) {
            $log->warning('Email pelamar tidak tersedia.', ['job_application_id' => $app->id]);

            return response()->json(['message' => 'Email pelamar tidak tersedia.'], 422);
        }

        $notif = in_array($data['event'], self::REPEATABLE_EVENTS, true)
            ? null
            : ApplicationNotification::where('job_application_id', $app->id)->where('event', $data['event'])->first();

        if ($notif && $notif->status === 'sent') {
            $log->info('already_sent - notif event ini sudah pernah terkirim, tidak dikirim ulang.', ['id' => $notif->id, 'event' => $data['event']]);

            return response()->json(['status' => 'already_sent', 'id' => $notif->id]);
        }

        if (! $notif) {
            $notif = isset($data['subject'], $data['body'])
                ? ApplicationNotification::create([
                    'job_application_id' => $app->id,
                    'event'              => $data['event'],
                    'subject'            => $data['subject'],
                    'body'               => $data['body'],
                    'status'             => 'draft',
                ])
                : ApplicationNotification::createDraftFor($app, $data['event'], $data['extra'] ?? []);
        }

        if (! $notif) {
            $log->error('Gagal menyusun notifikasi (createDraftFor mengembalikan null).', ['event' => $data['event']]);

            return response()->json(['message' => 'Gagal menyusun notifikasi.'], 500);
        }

        if ($data['hold'] ?? false) {
            $log->info('held - draft disimpan, TIDAK dikirim (nunggu flush).', ['id' => $notif->id, 'event' => $data['event']]);

            return response()->json(['status' => 'held', 'id' => $notif->id]);
        }

        Mail::to($email)->send(new ApplicantNotificationMail($notif)); // ShouldQueue -> masuk queue:work
        $notif->update(['status' => 'sent', 'sent_at' => now()]);

        $log->info('queued - email masuk antrean (queue:work harus jalan untuk mengirim).', [
            'id' => $notif->id, 'event' => $data['event'], 'email' => $email,
            'queue_connection' => config('queue.default'), 'mailer' => config('mail.default'),
        ]);

        return response()->json(['status' => 'queued', 'id' => $notif->id]);
    }

    /**
     * Kirim semua draft yang ditahan untuk daftar lamaran (mis. penolakan seleksi berkas).
     * Body: job_application_ids[], event? (batasi ke satu event).
     */
    public function flushNotifications(Request $request): JsonResponse
    {
        $data = $request->validate([
            'job_application_ids'   => ['required', 'array', 'min:1'],
            'job_application_ids.*' => ['string'],
            'event'                 => ['sometimes', Rule::in(self::NOTIFICATION_EVENTS)],
        ]);

        $drafts = ApplicationNotification::with('jobApplication.user')
            ->whereIn('job_application_id', $data['job_application_ids'])
            ->where('status', 'draft')
            ->when($data['event'] ?? null, fn ($q, $e) => $q->where('event', $e))
            ->get();

        $log = Log::channel('hris');
        $log->info('flushNotifications masuk.', [
            'jumlah_id' => count($data['job_application_ids']),
            'event' => $data['event'] ?? null,
            'draft_ketemu' => $drafts->count(),
        ]);

        $queued = 0;

        foreach ($drafts as $notif) {
            $email = $notif->jobApplication?->user?->email;

            if (! $email) {
                $log->warning('flush: draft dilewati - email pelamar kosong.', ['id' => $notif->id]);
                continue;
            }

            Mail::to($email)->send(new ApplicantNotificationMail($notif));
            $notif->update(['status' => 'sent', 'sent_at' => now()]);
            $queued++;
        }

        $log->info('flushNotifications selesai.', ['queued' => $queued, 'queue_connection' => config('queue.default'), 'mailer' => config('mail.default')]);

        return response()->json(['queued' => $queued]);
    }
}
