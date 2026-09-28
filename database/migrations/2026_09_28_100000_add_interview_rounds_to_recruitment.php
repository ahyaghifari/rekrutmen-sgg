<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wawancara berlapis - 1 pelamar bisa diwawancara lebih dari sekali (keputusan user 2026-09-28).
     *
     * - job_openings.interview_rounds : jumlah tahap wawancara lowongan itu, diatur per lowongan.
     * - interview_schedules.round     : tahap ke berapa jadwal ini.
     * - interview_schedules.name      : nama tahap, bebas diisi HR (mis. "Wawancara HR", "Wawancara User").
     * - interview_invitees.result     : hasil PER TAHAP. job_applications.interview_result tetap ada
     *                                  dan menyimpan hasil AKHIR, supaya halaman & filter lain yang
     *                                  membacanya tidak perlu diubah.
     *
     * Semua default = 1, jadi data lama otomatis terbaca sebagai wawancara tahap 1 dan alur satu
     * tahap tetap jalan apa adanya.
     */
    public function up(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->unsignedTinyInteger('interview_rounds')->default(1)->after('requires_psychotest');
        });

        Schema::table('interview_schedules', function (Blueprint $table) {
            $table->unsignedTinyInteger('round')->default(1)->after('job_opening_id');
            $table->string('name')->nullable()->after('round');
        });

        Schema::table('interview_invitees', function (Blueprint $table) {
            $table->enum('result', ['lulus', 'tidak_lulus'])->nullable()->after('job_application_id');
        });
    }

    public function down(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->dropColumn('interview_rounds');
        });

        Schema::table('interview_schedules', function (Blueprint $table) {
            $table->dropColumn(['round', 'name']);
        });

        Schema::table('interview_invitees', function (Blueprint $table) {
            $table->dropColumn('result');
        });
    }
};
