<?php

namespace Tests\Feature;

use App\Mail\ApplicantNotificationMail;
use App\Models\ApplicationNotification;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** php artisan test --filter=MailTemplateTest (tanpa database). */
class MailTemplateTest extends TestCase
{
    public function test_applicant_mail_is_multipart_with_plain_text_and_fallback_link(): void
    {
        $n = new ApplicationNotification([
            'job_application_id' => '01ABC', 'event' => 'interview_scheduled', 'subject' => 'Undangan Wawancara – Perawat',
            'body' => "Halo Budi,\n\nWawancara Anda dijadwalkan Kamis.\nLokasi: Ruang HR\n\nSalam,\nTim Rekrutmen",
        ]);

        Mail::mailer('array')->to('budi@x.test')->sendNow(new ApplicantNotificationMail($n)); // sendNow: mailable ini ShouldQueue
        $sent = Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();

        $this->assertStringContainsString('Wawancara Anda dijadwalkan Kamis.', $sent->getTextBody());
        $this->assertStringContainsString('/lamaran/01ABC', $sent->getTextBody());
        $html = $sent->getHtmlBody();
        $this->assertStringContainsString('<title>Undangan Wawancara', $html);
        $this->assertStringContainsString('name="viewport"', $html);
        $this->assertSame(2, substr_count($html, 'href="'.route('lamaran.show', '01ABC').'"')); // tombol + tautan cadangan
        $this->assertStringContainsString('<p style="margin:0 0 14px 0;">Halo Budi,</p>', $html);
        $this->assertStringContainsString('Lokasi: Ruang HR', $html);
    }

    public function test_verification_mail_has_plain_text_part(): void
    {
        config(['mail.default' => 'array']);
        $user = new \App\Models\User(['name' => 'Budi', 'email' => 'budi@x.test']);
        $user->id = 7;

        \Illuminate\Support\Facades\Notification::sendNow($user, new \App\Notifications\VerifyEmailNotification('budi@x.test'));
        $sent = Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();

        $this->assertStringContainsString('/verify-email/7/', $sent->getTextBody());
        $this->assertStringContainsString('Halo, Budi', $sent->getTextBody());
        $this->assertStringContainsString('Verifikasi Email Saya', $sent->getHtmlBody());
    }
}
