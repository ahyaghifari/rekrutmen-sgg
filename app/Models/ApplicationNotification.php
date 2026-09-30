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

    /** Tombol CTA di email untuk event yang butuh aksi pelamar. Null = tidak ada tombol. */
    public function cta(): ?array
    {
        return match ($this->event) {
            CompanyMessageTemplate::EVENT_QUIZ_SENT =>
                ['url' => route('tes-online.show'), 'label' => 'Kerjakan Tes'],
            CompanyMessageTemplate::EVENT_OFFERING_LETTER_READY =>
                ['url' => route('dashboard'), 'label' => 'Unggah Surat Penawaran'],
            CompanyMessageTemplate::EVENT_OFFERING_REVISION_REQUESTED =>
                ['url' => route('dashboard'), 'label' => 'Unggah Ulang Surat Penawaran'],
            // Form data surat penawaran ada di dashboard (route offering-letter.data), bukan di
            // halaman data diri kandidat - makanya tujuannya dashboard.
            CompanyMessageTemplate::EVENT_INTERVIEW_PASSED =>
                ['url' => route('dashboard'), 'label' => 'Lengkapi Data Surat Penawaran'],
            CompanyMessageTemplate::EVENT_MCU_PASSED =>
                ['url' => route('data-diri.edit'), 'label' => 'Setujui & Lengkapi Data Diri'],
            CompanyMessageTemplate::EVENT_ONBOARDING_INVITE =>
                ['url' => route('data-diri.edit'), 'label' => 'Lengkapi Data Diri Kandidat'],
            CompanyMessageTemplate::EVENT_INTERVIEW_SCHEDULED =>
                ['url' => route('lamaran.show', $this->job_application_id), 'label' => 'Lihat Jadwal Wawancara'],
            CompanyMessageTemplate::EVENT_MCU_SCHEDULED =>
                ['url' => route('lamaran.show', $this->job_application_id), 'label' => 'Lihat Jadwal MCU'],
            'interview_rescheduled' =>
                ['url' => route('lamaran.show', $this->job_application_id), 'label' => 'Lihat Jadwal Wawancara'],
            'mcu_rescheduled' =>
                ['url' => route('lamaran.show', $this->job_application_id), 'label' => 'Lihat Jadwal MCU'],
            CompanyMessageTemplate::EVENT_OFFLINE_TEST_SCHEDULED, 'offline_test_rescheduled' =>
                ['url' => route('lamaran.show', $this->job_application_id), 'label' => 'Lihat Jadwal Tes'],
            'offering_negotiation_replied' =>
                ['url' => route('dashboard'), 'label' => 'Lihat Balasan Negosiasi'],
            default => null,
        };
    }

    /**
     * Bikin baris lalu kirim emailnya lewat queue (ApplicantNotificationMail ShouldQueue ->
     * queue:work). Untuk event "positif" yang tidak perlu ditahan admin.
     */
    public static function createAndSend(JobApplication $app, string $event, array $extra = []): void
    {
        $notif = static::where('job_application_id', $app->id)->where('event', $event)->first()
            ?? static::createDraftFor($app, $event, $extra);

        if (! $notif || $notif->status === 'sent') {
            return;
        }

        $app->loadMissing('user');

        if ($app->user?->email) {
            \Illuminate\Support\Facades\Mail::to($app->user->email)
                ->send(new \App\Mail\ApplicantNotificationMail($notif));
            $notif->update(['status' => 'sent', 'sent_at' => now()]);
        }
    }

    /**
     * $allowRepeat: lewati penyaring "sudah pernah ada" untuk event yang memang boleh berulang -
     * dipakai interview_scheduled sejak 2026-09-30, karena wawancara bisa dijadwalkan berkali-kali
     * dan undangan sesi kedua dan seterusnya harus tetap terkirim. Pemanggil lain tidak berubah.
     */
    public static function createDraftFor(JobApplication $app, string $event, array $extra = [], bool $allowRepeat = false): static|null
    {
        if (! $allowRepeat && static::where('job_application_id', $app->id)->where('event', $event)->exists()) {
            return null;
        }

        $app->loadMissing('jobOpening');
        $companyId     = $app->jobOpening->company_id;
        $template      = CompanyMessageTemplate::forCompanyEvent($companyId, $event);
        $applicantName = $app->answersMap()['full_name'] ?? 'Pelamar';
        $jobTitle      = $app->jobOpening->title ?? '';

        // Undangan onboarding: ingatkan kalau data diri kandidat belum lengkap.
        if ($event === CompanyMessageTemplate::EVENT_ONBOARDING_INVITE) {
            $app->loadMissing('candidate');
            $extra['data_diri_belum_lengkap'] = ! ($app->candidate?->requiredDataComplete() ?? false);
        }

        // {tahap} = nama sesi wawancara ("Wawancara HR", "Wawancara Tahap 2"), dikirim HRIS sejak
        // wawancara boleh berkali-kali. Template lama yang tidak memakainya tidak terpengaruh.
        $placeholders = ['{nama}', '{posisi}', '{tanggal}', '{waktu}', '{lokasi}', '{catatan}', '{tahap}'];
        $values       = [
            $applicantName,
            $jobTitle,
            $extra['interview_date'] ?? '-',
            $extra['interview_time'] ?? '-',
            $extra['location']       ?? '-',
            $extra['notes']          ?? '',
            $extra['tahap']          ?? '-',
        ];
        $replace = fn (string $s) => str_replace($placeholders, $values, $s);

        $subject = $template
            ? $replace($template->subject)
            : static::defaultSubject($event, $jobTitle);

        $body = $template
            ? $replace($template->body)
            : static::defaultBody($event, $applicantName, $jobTitle, $extra);

        return static::create([
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
            CompanyMessageTemplate::EVENT_QUIZ_SENT           => "Tes Sudah Bisa Dikerjakan – {$jobTitle}",
            CompanyMessageTemplate::EVENT_QUIZ_PASSED         => "Selamat! Kamu Lulus Tes – {$jobTitle}",
            CompanyMessageTemplate::EVENT_QUIZ_REJECTED       => "Update Lamaran Kamu – {$jobTitle}",
            CompanyMessageTemplate::EVENT_INTERVIEW_SCHEDULED => "Undangan Wawancara – {$jobTitle}",
            CompanyMessageTemplate::EVENT_INTERVIEW_PASSED    => "Selamat! Kamu Lolos Wawancara – {$jobTitle}",
            CompanyMessageTemplate::EVENT_INTERVIEW_REJECTED  => "Update Lamaran Kamu – {$jobTitle}",
            CompanyMessageTemplate::EVENT_MCU_SCHEDULED       => "Jadwal Medical Check Up – {$jobTitle}",
            CompanyMessageTemplate::EVENT_MCU_PASSED          => "Selamat! Kamu Lulus MCU – {$jobTitle}",
            CompanyMessageTemplate::EVENT_MCU_REJECTED        => "Update Lamaran Kamu – {$jobTitle}",
            CompanyMessageTemplate::EVENT_ONBOARDING_INVITE   => "Selamat Bergabung! Undangan TTD & Onboarding – {$jobTitle}",
            CompanyMessageTemplate::EVENT_OFFERING_LETTER_READY => "Surat Penawaran Kerja Sudah Bisa Diunduh – {$jobTitle}",
            CompanyMessageTemplate::EVENT_OFFERING_SIGNED_RECEIVED => "Surat Penawaran Bertanda Tangan Diterima – {$jobTitle}",
            CompanyMessageTemplate::EVENT_MANUAL_REJECTED     => "Update Lamaran Kamu – {$jobTitle}",
            CompanyMessageTemplate::EVENT_OFFLINE_TEST_SCHEDULED => "Jadwal Tes Offline – {$jobTitle}",
            default                                           => "Update Lamaran Kamu – {$jobTitle}",
        };
    }

    private static function defaultBody(string $event, string $name, string $jobTitle, array $extra = []): string
    {
        if ($event === CompanyMessageTemplate::EVENT_SCREENING_PASSED) {
            return "Halo {$name},\n\nKami dengan senang hati menginformasikan bahwa berkas lamaran kamu untuk posisi {$jobTitle} telah berhasil lolos tahap seleksi berkas.\n\nTahap selanjutnya adalah tes online. Tim kami akan segera memberi tahu kamu begitu tesnya sudah bisa dikerjakan lewat dashboard akunmu.\n\nTetap semangat dan terima kasih atas ketertarikanmu bergabung bersama kami!\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_INTERVIEW_SCHEDULED) {
            // ?? di tiap kunci: HRIS tidak selalu mengirim waktu/lokasi/catatan, dan tanpa ini tiap
            // undangan menyemburkan "Undefined array key" ke log.
            $date     = $extra['interview_date'] ?? '-';
            $time     = ($extra['interview_time'] ?? null) ? " pukul {$extra['interview_time']}" : '';
            $location = ($extra['location'] ?? null) ? "\nLokasi/Link: {$extra['location']}" : '';
            $notes    = ($extra['notes'] ?? null) ? "\n\nCatatan: {$extra['notes']}" : '';
            // Nama sesi ("Wawancara HR") disebut kalau ada, supaya undangan sesi kedua tidak terbaca
            // seolah-olah undangan wawancara pertama.
            $tahap    = ($extra['tahap'] ?? null) ? " ({$extra['tahap']})" : '';

            return "Halo {$name},\n\nSelamat! Kamu terpilih untuk mengikuti tahap wawancara{$tahap} posisi {$jobTitle}.\n\nWawancara dijadwalkan pada:\nTanggal: {$date}{$time}{$location}{$notes}\n\nMohon konfirmasi kehadiranmu. Kami tunggu ya!\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_QUIZ_SENT) {
            return "Halo {$name},\n\nSelamat! Kamu lolos ke tahap Tes untuk posisi {$jobTitle}.\n\nTes bisa kamu kerjakan kapan saja lewat dashboard akunmu. Perlu diperhatikan: begitu kamu menekan tombol \"Mulai Tes\", pengerjaan harus diselesaikan dalam batas waktu yang ditentukan — jadi pastikan waktumu cukup sebelum memulai.\n\nSemangat mengerjakan!\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_QUIZ_PASSED) {
            return "Halo {$name},\n\nSelamat! Kamu dinyatakan lulus tes untuk posisi {$jobTitle}.\n\nTim rekrutmen kami akan segera menghubungimu untuk tahap wawancara.\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_INTERVIEW_PASSED) {
            // Kandidat diminta MENGISI DATA dulu, bukan menunggu (permintaan user 2026-09-30):
            // surat penawarannya baru bisa disusun setelah datanya masuk, dan formnya ada di
            // dashboard (lihat route offering-letter.data).
            return "Halo {$name},\n\nSelamat! Kamu dinyatakan lolos tahap wawancara untuk posisi {$jobTitle}.\n\nTahap selanjutnya adalah penawaran kerja (offering letter). Mohon masuk ke dashboard akunmu dan lengkapi data diri untuk surat penawaran — suratnya bisa kamu unduh setelahnya.\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_OFFERING_LETTER_READY) {
            return "Halo {$name},\n\nSelamat! Surat penawaran kerja (offering letter) untuk posisi {$jobTitle} sudah bisa kamu unduh lewat dashboard akunmu.\n\nSetelah dibaca dan ditandatangani, mohon unggah kembali surat tersebut lewat dashboard yang sama.\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_OFFERING_SIGNED_RECEIVED) {
            return "Halo {$name},\n\nSurat penawaran kerja yang sudah kamu tandatangani untuk posisi {$jobTitle} telah kami terima.\n\nTim rekrutmen akan memeriksa dan mengonfirmasi surat tersebut pada jam kerja. Kamu akan kami kabari lagi lewat email begitu ada hasilnya.\n\nTerima kasih atas kesabaranmu.\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_MCU_PASSED) {
            return "Halo {$name},\n\nSelamat! Kamu dinyatakan fit/lulus Medical Check Up untuk posisi {$jobTitle}.\n\nSebelum lanjut ke tahap TTD & Onboarding, mohon lengkapi data diri kandidat lewat dashboard akunmu. Kamu perlu menyetujui semua persetujuan yang ditampilkan dulu sebelum bisa mengisi data diri.\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_MCU_SCHEDULED) {
            $date     = $extra['interview_date'] ?? '-';
            $time     = $extra['interview_time'] ? " pukul {$extra['interview_time']}" : '';
            $location = $extra['location'] ? "\nLokasi: {$extra['location']}" : '';
            $notes    = $extra['notes'] ? "\n\nCatatan: {$extra['notes']}" : '';
            return "Halo {$name},\n\nSurat penawaran kerja (offering letter) kamu untuk posisi {$jobTitle} sudah kami terima. Proses dilanjutkan ke tahap Medical Check Up (MCU).\n\nMCU dijadwalkan pada:\nTanggal: {$date}{$time}{$location}{$notes}\n\nMohon konfirmasi kehadiranmu. Kami tunggu ya!\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_OFFLINE_TEST_SCHEDULED) {
            $date     = $extra['interview_date'] ?? '-';
            $time     = $extra['interview_time'] ? " pukul {$extra['interview_time']}" : '';
            $location = $extra['location'] ? "\nLokasi: {$extra['location']}" : '';
            $notes    = $extra['notes'] ? "\n\nCatatan: {$extra['notes']}" : '';
            return "Halo {$name},\n\nKamu diundang mengikuti tes offline untuk posisi {$jobTitle}.\n\nTes dijadwalkan pada:\nTanggal: {$date}{$time}{$location}{$notes}\n\nMohon konfirmasi kehadiranmu. Kami tunggu ya!\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_QUIZ_REJECTED) {
            return "Halo {$name},\n\nTerima kasih telah mengikuti proses seleksi untuk posisi {$jobTitle}.\n\nSetelah meninjau hasil Tes, kami sampaikan bahwa pada tahap ini kami belum dapat melanjutkan proses lamaran kamu.\n\nJangan menyerah — terus tingkatkan kemampuanmu dan kami doakan yang terbaik untukmu.\n\nSalam hangat,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_INTERVIEW_REJECTED) {
            return "Halo {$name},\n\nTerima kasih telah meluangkan waktu untuk mengikuti proses wawancara posisi {$jobTitle}.\n\nSetelah melalui pertimbangan, kami sampaikan bahwa pada tahap ini kami belum dapat melanjutkan proses lamaran kamu.\n\nKami sangat menghargai antusiasme dan usahamu. Jangan menyerah — terus tingkatkan diri dan kami doakan yang terbaik untukmu.\n\nSalam hangat,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_MANUAL_REJECTED) {
            $notes = $extra['notes'] ? "\n\nCatatan: {$extra['notes']}" : '';
            return "Halo {$name},\n\nTerima kasih atas partisipasimu dalam proses seleksi untuk posisi {$jobTitle}.\n\nKami informasikan bahwa proses lamaranmu kami hentikan pada tahap ini.{$notes}\n\nKami doakan yang terbaik untukmu.\n\nSalam hangat,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_MCU_REJECTED) {
            return "Halo {$name},\n\nTerima kasih telah menjalani proses Medical Check Up (MCU) untuk posisi {$jobTitle}.\n\nSetelah melalui evaluasi hasil MCU, kami sampaikan bahwa pada tahap ini kami belum dapat melanjutkan proses lamaran kamu.\n\nKami sangat menghargai kesungguhanmu selama proses seleksi. Kami doakan yang terbaik untukmu.\n\nSalam hangat,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_ONBOARDING_INVITE) {
            $jadwal = ! empty($extra['onboarding_date'])
                ? "\n\nJadwal onboarding kamu: {$extra['onboarding_date']}. Mohon hadir tepat waktu; detail lokasi dan agenda menyusul dari tim rekrutmen."
                : "\n\nTim rekrutmen kami akan segera menghubungimu untuk jadwal dan detail lebih lanjut.";

            $dataDiri = ! empty($extra['data_diri_belum_lengkap'])
                ? "\n\nCatatan: data diri kandidat kamu belum lengkap. Mohon lengkapi lewat dashboard akunmu sebelum hari onboarding supaya proses administrasi karyawan berjalan lancar."
                : '';

            return "Halo {$name},\n\nSelamat! Kamu dinyatakan lolos seluruh tahap seleksi untuk posisi {$jobTitle} di Syifa Global Group.\n\nLangkah terakhir: penandatanganan kontrak dan onboarding.{$jadwal}{$dataDiri}\n\nSelamat bergabung bersama kami!\n\nSalam,\nTim Rekrutmen";
        }

        return "Halo {$name},\n\nTerima kasih telah melamar untuk posisi {$jobTitle}.\n\nSetelah melalui proses seleksi berkas, kami sampaikan bahwa pada tahap ini kami belum dapat melanjutkan proses lamaran kamu.\n\nJangan menyerah ya! Terus tingkatkan diri dan semoga kesuksesan selalu menyertaimu.\n\nSalam hangat,\nTim Rekrutmen";
    }
}
