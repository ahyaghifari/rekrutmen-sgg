<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationNotification extends Model
{
    use HasUlids;

    protected $fillable = ['job_application_id', 'event', 'subject', 'body', 'status', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public static function createDraftFor(JobApplication $app, string $event, array $extra = []): void
    {
        $exists = static::where('job_application_id', $app->id)->where('event', $event)->exists();
        if ($exists) return;

        $app->loadMissing('jobOpening');
        $companyId     = $app->jobOpening->company_id;
        $template      = CompanyMessageTemplate::forCompanyEvent($companyId, $event);
        $applicantName = $app->answersMap()['full_name'] ?? 'Pelamar';
        $jobTitle      = $app->jobOpening->title ?? '';

        $placeholders = ['{nama}', '{posisi}', '{tanggal}', '{waktu}', '{lokasi}', '{catatan}'];
        $values       = [
            $applicantName,
            $jobTitle,
            $extra['interview_date'] ?? '-',
            $extra['interview_time'] ?? '-',
            $extra['location']       ?? '-',
            $extra['notes']          ?? '',
        ];
        $replace = fn (string $s) => str_replace($placeholders, $values, $s);

        $subject = $template
            ? $replace($template->subject)
            : static::defaultSubject($event, $jobTitle);

        $body = $template
            ? $replace($template->body)
            : static::defaultBody($event, $applicantName, $jobTitle, $extra);

        static::create([
            'job_application_id' => $app->id,
            'event'              => $event,
            'subject'            => $subject,
            'body'               => $body,
            'status'             => 'draft',
        ]);
    }

    private static function defaultSubject(string $event, string $jobTitle): string
    {
        return match ($event) {
            CompanyMessageTemplate::EVENT_SCREENING_PASSED    => "Selamat! Kamu Lolos Seleksi Berkas – {$jobTitle}",
            CompanyMessageTemplate::EVENT_INTERVIEW_SCHEDULED => "Undangan Wawancara – {$jobTitle}",
            default                                           => "Update Lamaran Kamu – {$jobTitle}",
        };
    }

    private static function defaultBody(string $event, string $name, string $jobTitle, array $extra = []): string
    {
        if ($event === CompanyMessageTemplate::EVENT_SCREENING_PASSED) {
            return "Halo {$name},\n\nKami dengan senang hati menginformasikan bahwa berkas lamaran kamu untuk posisi {$jobTitle} telah berhasil lolos tahap seleksi berkas.\n\nTim kami akan segera menghubungi kamu untuk informasi lebih lanjut terkait jadwal wawancara.\n\nTetap semangat dan terima kasih atas ketertarikanmu bergabung bersama kami!\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_INTERVIEW_SCHEDULED) {
            $date     = $extra['interview_date'] ?? '-';
            $time     = $extra['interview_time'] ? " pukul {$extra['interview_time']}" : '';
            $location = $extra['location'] ? "\nLokasi/Link: {$extra['location']}" : '';
            $notes    = $extra['notes'] ? "\n\nCatatan: {$extra['notes']}" : '';
            return "Halo {$name},\n\nSelamat! Kamu terpilih untuk mengikuti tahap wawancara posisi {$jobTitle}.\n\nWawancara dijadwalkan pada:\nTanggal: {$date}{$time}{$location}{$notes}\n\nMohon konfirmasi kehadiranmu. Kami tunggu ya!\n\nSalam,\nTim Rekrutmen";
        }

        return "Halo {$name},\n\nTerima kasih telah melamar untuk posisi {$jobTitle}.\n\nSetelah melalui proses seleksi berkas, kami sampaikan bahwa pada tahap ini kami belum dapat melanjutkan proses lamaran kamu.\n\nJangan menyerah ya! Terus tingkatkan diri dan semoga kesuksesan selalu menyertaimu.\n\nSalam hangat,\nTim Rekrutmen";
    }
}
