<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * interview_invitees.job_application_id masih UNIK - peninggalan zaman wawancara cuma sekali.
     * Sejak wawancara boleh dijadwalkan berkali-kali untuk pelamar yang sama, batasan itu bikin
     * "jadwalkan wawancara berikutnya" gagal dengan SQLSTATE[23000] duplicate entry.
     *
     * Diganti unik gabungan (interview_schedule_id, job_application_id): satu orang tetap tidak bisa
     * diundang dua kali ke SESI YANG SAMA, tapi bebas ikut sesi lain.
     *
     * Urutannya penting: indeks unik lama dipakai foreign key job_application_id, jadi MySQL menolak
     * melepasnya sebelum ada indeks lain yang kolom terdepannya job_application_id (error 1553).
     * Unik gabungan tidak bisa menggantikan perannya karena kolom itu ada di urutan kedua - makanya
     * indeks biasa dibuat lebih dulu.
     *
     * mcu_invitees sengaja TIDAK diikutkan - MCU masih sekali per pelamar.
     */
    public function up(): void
    {
        Schema::table('interview_invitees', function (Blueprint $table) {
            $table->index('job_application_id', 'interview_invitees_job_application_id_index');
            $table->dropUnique('interview_invitees_job_application_id_unique');
            $table->unique(['interview_schedule_id', 'job_application_id'], 'interview_invitees_schedule_application_unique');
        });
    }

    public function down(): void
    {
        Schema::table('interview_invitees', function (Blueprint $table) {
            $table->dropUnique('interview_invitees_schedule_application_unique');
            $table->unique('job_application_id', 'interview_invitees_job_application_id_unique');
            $table->dropIndex('interview_invitees_job_application_id_index');
        });
    }
};
