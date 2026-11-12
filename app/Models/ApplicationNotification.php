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
            // Lolos berkas: tes online langsung aktif (quiz_sent_at terisi) -> tombol langsung ke tes.
            CompanyMessageTemplate::EVENT_SCREENING_PASSED =>
                $this->jobApplication?->quiz_sent_at ? ['url' => route('tes-online.show'), 'label' => 'Kerjakan Tes'] : null,
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
            'interview_rescheduled', 'interview_reschedule_approved', 'interview_reschedule_rejected' =>
                ['url' => route('lamaran.show', $this->job_application_id), 'label' => 'Lihat Jadwal Wawancara'],
            'mcu_rescheduled', 'mcu_reschedule_approved', 'mcu_reschedule_rejected' =>
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

        // Lolos berkas: email menyebut tes online sudah bisa dikerjakan hanya kalau tesnya memang sudah aktif untuknya.
        if ($event === CompanyMessageTemplate::EVENT_SCREENING_PASSED) {
            $extra['bisa_tes'] = (bool) $app->quiz_sent_at;
        }

        // {tahap} = nama sesi wawancara ("Wawancara HR", "Wawancara Tahap 2"), dikirim HRIS sejak
        // wawancara boleh berkali-kali. Template lama yang tidak memakainya tidak terpengaruh.
        $placeholders = ['{nama}', '{posisi}', '{tanggal}', '{waktu}', '{lokasi}', '{catatan}', '{tahap}'];
        $values       = [
            $applicantName,
            $jobTitle,
            $extra['interview_date'] ?? '-',
            isset($extra['interview_time']) ? $extra['interview_time'] . ' WITA' : '-',
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
            CompanyMessageTemplate::EVENT_SCREENING_PASSED    => "Selamat! Anda Lolos Seleksi Berkas – {$jobTitle}",
            CompanyMessageTemplate::EVENT_QUIZ_SENT           => "Tes Sudah Bisa Dikerjakan – {$jobTitle}",
            CompanyMessageTemplate::EVENT_QUIZ_PASSED         => "Selamat! Anda Lulus Tes – {$jobTitle}",
            CompanyMessageTemplate::EVENT_QUIZ_REJECTED       => "Update Lamaran Anda – {$jobTitle}",
            CompanyMessageTemplate::EVENT_INTERVIEW_SCHEDULED => "Undangan Wawancara – {$jobTitle}",
            CompanyMessageTemplate::EVENT_INTERVIEW_PASSED    => "Selamat! Anda Lolos Wawancara – {$jobTitle}",
            CompanyMessageTemplate::EVENT_INTERVIEW_REJECTED  => "Update Lamaran Anda – {$jobTitle}",
            CompanyMessageTemplate::EVENT_MCU_SCHEDULED       => "Jadwal Medical Check Up – {$jobTitle}",
            CompanyMessageTemplate::EVENT_MCU_PASSED          => "Selamat! Anda Lulus MCU – {$jobTitle}",
            CompanyMessageTemplate::EVENT_MCU_REJECTED        => "Update Lamaran Anda – {$jobTitle}",
            CompanyMessageTemplate::EVENT_ONBOARDING_INVITE   => "Selamat Bergabung! Undangan TTD & Onboarding – {$jobTitle}",
            CompanyMessageTemplate::EVENT_OFFERING_LETTER_READY => "Surat Penawaran Kerja Sudah Bisa Diunduh – {$jobTitle}",
            CompanyMessageTemplate::EVENT_OFFERING_SIGNED_RECEIVED => "Surat Penawaran Bertanda Tangan Diterima – {$jobTitle}",
            CompanyMessageTemplate::EVENT_MANUAL_REJECTED     => "Update Lamaran Anda – {$jobTitle}",
            CompanyMessageTemplate::EVENT_OFFLINE_TEST_SCHEDULED => "Jadwal Tes Offline – {$jobTitle}",
            'interview_reschedule_approved' => "Reschedule Wawancara Disetujui – {$jobTitle}",
            'interview_reschedule_rejected' => "Reschedule Wawancara Tidak Disetujui – {$jobTitle}",
            'mcu_reschedule_approved'       => "Reschedule MCU Disetujui – {$jobTitle}",
            'mcu_reschedule_rejected'       => "Reschedule MCU Tidak Disetujui – {$jobTitle}",
            default                                          => "Update Lamaran Anda – {$jobTitle}",
        };
    }

    private static function defaultBody(string $event, string $name, string $jobTitle, array $extra = []): string
    {
        if ($event === CompanyMessageTemplate::EVENT_SCREENING_PASSED) {
            // Tes online sudah aktif sejak lolos berkas: pelamar bisa langsung mengerjakan, tidak perlu menunggu kabar lagi.
            if ($extra['bisa_tes'] ?? false) {
                return "Halo {$name},\n\nSelamat! Berkas lamaran Anda untuk posisi {$jobTitle} dinyatakan LOLOS seleksi berkas.\n\nTahap selanjutnya adalah tes online, dan tes ini sudah bisa Anda kerjakan sekarang lewat dashboard akun Anda. Perlu diperhatikan: begitu Anda menekan tombol \"Mulai Tes\", pengerjaan harus diselesaikan dalam batas waktu yang ditentukan — jadi pastikan waktu Anda cukup sebelum memulai.\n\nSemangat mengerjakan, dan terima kasih atas ketertarikan Anda bergabung bersama kami!\n\nSalam,\nTim Rekrutmen";
            }

            return "Halo {$name},\n\nKami dengan senang hati menginformasikan bahwa berkas lamaran Anda untuk posisi {$jobTitle} telah berhasil lolos tahap seleksi berkas.\n\nTahap selanjutnya adalah tes online. Tim kami akan segera memberi tahu Anda begitu tesnya sudah bisa dikerjakan lewat dashboard akun Anda.\n\nTetap semangat dan terima kasih atas ketertarikan Anda bergabung bersama kami!\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_INTERVIEW_SCHEDULED) {
            // ?? di tiap kunci: HRIS tidak selalu mengirim waktu/lokasi/catatan, dan tanpa ini tiap
            // undangan menyemburkan "Undefined array key" ke log.
            $date     = $extra['interview_date'] ?? '-';
            $time     = ($extra['interview_time'] ?? null) ? " pukul {$extra['interview_time']} WITA" : '';
            $location = ($extra['location'] ?? null) ? "\nLokasi/Link: {$extra['location']}" : '';
            $notes    = ($extra['notes'] ?? null) ? "\n\nCatatan: {$extra['notes']}" : '';
            // Nama sesi ("Wawancara HR") disebut kalau ada, supaya undangan sesi kedua tidak terbaca
            // seolah-olah undangan wawancara pertama.
            $tahap    = ($extra['tahap'] ?? null) ? " ({$extra['tahap']})" : '';

            return "Halo {$name},\n\nSelamat! Anda terpilih untuk mengikuti tahap wawancara{$tahap} posisi {$jobTitle}.\n\nWawancara dijadwalkan pada:\nTanggal: {$date}{$time}{$location}{$notes}\n\nMohon konfirmasi kehadiran Anda. Kami tunggu ya!\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_QUIZ_SENT) {
            return "Halo {$name},\n\nSelamat! Anda lolos ke tahap Tes untuk posisi {$jobTitle}.\n\nTes bisa Anda kerjakan kapan saja lewat dashboard akun Anda. Perlu diperhatikan: begitu Anda menekan tombol \"Mulai Tes\", pengerjaan harus diselesaikan dalam batas waktu yang ditentukan — jadi pastikan waktu Anda cukup sebelum memulai.\n\nSemangat mengerjakan!\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_QUIZ_PASSED) {
            return "Halo {$name},\n\nSelamat! Anda dinyatakan lulus tes untuk posisi {$jobTitle}.\n\nTim rekrutmen kami akan segera menghubungi Anda untuk tahap wawancara.\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_INTERVIEW_PASSED) {
            // Kandidat diminta MENGISI DATA dulu, bukan menunggu (permintaan user 2026-09-30):
            // surat penawarannya baru bisa disusun setelah datanya masuk, dan formnya ada di
            // dashboard (lihat route offering-letter.data).
            return "Halo {$name},\n\nSelamat! Anda dinyatakan lolos tahap wawancara untuk posisi {$jobTitle}.\n\nTahap selanjutnya adalah penawaran kerja (offering letter). Mohon masuk ke dashboard akun Anda dan lengkapi data diri untuk surat penawaran — suratnya bisa Anda unduh setelahnya.\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_OFFERING_LETTER_READY) {
            return "Halo {$name},\n\nSelamat! Surat penawaran kerja (offering letter) untuk posisi {$jobTitle} sudah bisa Anda unduh lewat dashboard akun Anda.\n\nSetelah dibaca dan ditandatangani, mohon unggah kembali surat tersebut lewat dashboard yang sama.\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_OFFERING_SIGNED_RECEIVED) {
            return "Halo {$name},\n\nSurat penawaran kerja yang sudah Anda tandatangani untuk posisi {$jobTitle} telah kami terima.\n\nTim rekrutmen akan memeriksa dan mengonfirmasi surat tersebut pada jam kerja. Anda akan kami kabari lagi lewat email begitu ada hasilnya.\n\nTerima kasih atas kesabaran Anda.\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_MCU_PASSED) {
            return "Halo {$name},\n\nSelamat! Anda dinyatakan fit/lulus Medical Check Up untuk posisi {$jobTitle}.\n\nSebelum lanjut ke tahap TTD & Onboarding, mohon lengkapi data diri kandidat lewat dashboard akun Anda. Anda perlu menyetujui semua persetujuan yang ditampilkan dulu sebelum bisa mengisi data diri.\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_MCU_SCHEDULED) {
            $date     = $extra['interview_date'] ?? '-';
            $time     = $extra['interview_time'] ? " pukul {$extra['interview_time']} WITA" : '';
            $location = $extra['location'] ? "\nLokasi: {$extra['location']}" : '';
            $notes    = $extra['notes'] ? "\n\nCatatan: {$extra['notes']}" : '';
            return "Halo {$name},\n\nSurat penawaran kerja (offering letter) Anda untuk posisi {$jobTitle} sudah kami terima. Proses dilanjutkan ke tahap Medical Check Up (MCU).\n\nMCU dijadwalkan pada:\nTanggal: {$date}{$time}{$location}{$notes}\n\nMohon konfirmasi kehadiran Anda. Kami tunggu ya!\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_OFFLINE_TEST_SCHEDULED) {
            $date     = $extra['interview_date'] ?? '-';
            $time     = $extra['interview_time'] ? " pukul {$extra['interview_time']} WITA" : '';
            $location = $extra['location'] ? "\nLokasi: {$extra['location']}" : '';
            $notes    = $extra['notes'] ? "\n\nCatatan: {$extra['notes']}" : '';
            return "Halo {$name},\n\nAnda diundang mengikuti tes offline untuk posisi {$jobTitle}.\n\nTes dijadwalkan pada:\nTanggal: {$date}{$time}{$location}{$notes}\n\nMohon konfirmasi kehadiran Anda. Kami tunggu ya!\n\nSalam,\nTim Rekrutmen";
        }

        // Keputusan HR atas permintaan reschedule pelamar (lihat NotifiesRescheduleDecision).
        if (preg_match('/^(interview|mcu)_reschedule_(approved|rejected)$/', $event, $m)) {
            $what     = $m[1] === 'mcu' ? 'Medical Check Up (MCU)' : 'wawancara';
            $time     = ! empty($extra['interview_time']) ? " pukul {$extra['interview_time']} WITA" : '';
            $location = ! empty($extra['location']) ? "\nLokasi: {$extra['location']}" : '';
            $notes    = ! empty($extra['notes']) ? "\n\nCatatan: {$extra['notes']}" : '';
            $date     = $extra['interview_date'] ?? '-';

            // "Setuju Ditetapkan": reschedule disetujui, tapi HR menetapkan jadwal baru sendiri (bukan usulan pelamar).
            if ($m[2] === 'approved' && ! empty($extra['ditetapkan_hr'])) {
                return "Halo {$name},\n\nPermintaan reschedule {$what} Anda untuk posisi {$jobTitle} telah DISETUJUI. Namun, jadwal barunya ditetapkan oleh tim rekrutmen dan mungkin berbeda dari usulan Anda.\n\nJadwal yang ditetapkan:\nTanggal: {$date}{$time}{$location}{$notes}\n\nMohon hadir sesuai jadwal yang ditetapkan tersebut. Terima kasih atas pengertian Anda.\n\nSalam,\nTim Rekrutmen";
            }

            return $m[2] === 'approved'
                ? "Halo {$name},\n\nPermintaan reschedule {$what} Anda untuk posisi {$jobTitle} telah DISETUJUI.\n\nJadwal baru Anda:\nTanggal: {$date}{$time}{$location}{$notes}\n\nMohon hadir sesuai jadwal baru tersebut.\n\nSalam,\nTim Rekrutmen"
                : "Halo {$name},\n\nMohon maaf, permintaan reschedule {$what} Anda untuk posisi {$jobTitle} belum dapat kami setujui.\n\nSilakan hadir sesuai jadwal awal yang sudah ditentukan:\nTanggal: {$date}{$time}{$location}{$notes}\n\nTerima kasih atas pengertian Anda.\n\nSalam,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_QUIZ_REJECTED) {
            return "Halo {$name},\n\nTerima kasih telah mengikuti proses seleksi untuk posisi {$jobTitle}.\n\nSetelah meninjau hasil Tes, kami sampaikan bahwa pada tahap ini kami belum dapat melanjutkan proses lamaran Anda.\n\nJangan menyerah — terus tingkatkan kemampuan Anda dan kami doakan yang terbaik untuk Anda.\n\nSalam hangat,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_INTERVIEW_REJECTED) {
            return "Halo {$name},\n\nTerima kasih telah meluangkan waktu untuk mengikuti proses wawancara posisi {$jobTitle}.\n\nSetelah melalui pertimbangan, kami sampaikan bahwa pada tahap ini kami belum dapat melanjutkan proses lamaran Anda.\n\nKami sangat menghargai antusiasme dan usaha Anda. Jangan menyerah — terus tingkatkan diri dan kami doakan yang terbaik untuk Anda.\n\nSalam hangat,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_MANUAL_REJECTED) {
            $notes = $extra['notes'] ? "\n\nCatatan: {$extra['notes']}" : '';
            return "Halo {$name},\n\nTerima kasih atas partisipasi Anda dalam proses seleksi untuk posisi {$jobTitle}.\n\nKami informasikan bahwa proses lamaran Anda kami hentikan pada tahap ini.{$notes}\n\nKami doakan yang terbaik untuk Anda.\n\nSalam hangat,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_MCU_REJECTED) {
            return "Halo {$name},\n\nTerima kasih telah menjalani proses Medical Check Up (MCU) untuk posisi {$jobTitle}.\n\nSetelah melalui evaluasi hasil MCU, kami sampaikan bahwa pada tahap ini kami belum dapat melanjutkan proses lamaran Anda.\n\nKami sangat menghargai kesungguhan Anda selama proses seleksi. Kami doakan yang terbaik untuk Anda.\n\nSalam hangat,\nTim Rekrutmen";
        }

        if ($event === CompanyMessageTemplate::EVENT_ONBOARDING_INVITE) {
            $jadwal = ! empty($extra['onboarding_date'])
                ? "\n\nJadwal onboarding Anda: {$extra['onboarding_date']}. Mohon hadir tepat waktu; detail lokasi dan agenda menyusul dari tim rekrutmen."
                : "\n\nTim rekrutmen kami akan segera menghubungi Anda untuk jadwal dan detail lebih lanjut.";

            $dataDiri = ! empty($extra['data_diri_belum_lengkap'])
                ? "\n\nCatatan: data diri kandidat Anda belum lengkap. Mohon lengkapi lewat dashboard akun Anda sebelum hari onboarding supaya proses administrasi karyawan berjalan lancar."
                : '';

            return "Halo {$name},\n\nSelamat! Anda dinyatakan lolos seluruh tahap seleksi untuk posisi {$jobTitle} di Syifa Global Group.\n\nLangkah terakhir: penandatanganan kontrak dan onboarding.{$jadwal}{$dataDiri}\n\nSelamat bergabung bersama kami!\n\nSalam,\nTim Rekrutmen";
        }

        return "Halo {$name},\n\nTerima kasih telah melamar untuk posisi {$jobTitle}.\n\nSetelah melalui proses seleksi berkas, kami sampaikan bahwa pada tahap ini kami belum dapat melanjutkan proses lamaran Anda.\n\nJangan menyerah ya! Terus tingkatkan diri dan semoga kesuksesan selalu menyertai Anda.\n\nSalam hangat,\nTim Rekrutmen";
    }
}
