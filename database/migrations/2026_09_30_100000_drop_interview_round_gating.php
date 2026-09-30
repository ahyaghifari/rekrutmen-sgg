<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Koreksi arah dari 2026_09_28_100000 (keputusan user 2026-09-30): wawancara memang bisa
     * dijadwalkan lebih dari sekali untuk 1 pelamar, TAPI keputusan lanjut/tidak BUKAN per jadwal -
     * dia menempel di status pelamarnya (job_applications.interview_result + status).
     *
     * Jadi dua kolom yang tadinya jadi pagar per-tahap dilepas:
     * - job_openings.interview_rounds : tidak perlu dibatasi, penjadwalan bebas berapa kali
     * - interview_invitees.result     : hasil bukan milik jadwal
     *
     * interview_schedules.round & name TETAP dipakai - itu cuma penanda & nama sesi ("Wawancara HR",
     * "Wawancara User"), bukan penentu keputusan.
     */
    public function up(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->dropColumn('interview_rounds');
        });

        Schema::table('interview_invitees', function (Blueprint $table) {
            $table->dropColumn('result');
        });
    }

    public function down(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->unsignedTinyInteger('interview_rounds')->default(1)->after('requires_psychotest');
        });

        Schema::table('interview_invitees', function (Blueprint $table) {
            $table->enum('result', ['lulus', 'tidak_lulus'])->nullable()->after('job_application_id');
        });
    }
};
