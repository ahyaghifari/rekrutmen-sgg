<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satu sesi wawancara bisa dinilai beberapa penilai (interviewer): interview_results tidak lagi unik per
 * undangan sesi; interviewer pindah ke tiap hasil. Jadwal diberi penanda "wawancara user" (bukan HR).
 * Kolom interviewer lama di interview_invitees dibiarkan (hanya cadangan, tidak dipakai lagi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_schedules', function (Blueprint $table) {
            $table->boolean('is_user_interview')->default(false);
        });

        Schema::table('interview_results', function (Blueprint $table) {
            // Interviewer penilai ini (ULID employees.id HRIS, tanpa FK karena beda database) + salinan nama.
            $table->string('interviewer_employee_id', 26)->nullable();
            $table->string('interviewer_name')->nullable();
            // FK butuh indeks: pasang indeks biasa dulu, baru unik dilepas.
            $table->index('interview_invitee_id', 'interview_results_invitee_idx');
        });

        Schema::table('interview_results', function (Blueprint $table) {
            $table->dropUnique(['interview_invitee_id']);
        });

        // Hasil yang sudah ada mewarisi interviewer dari undangannya.
        DB::statement('
            UPDATE interview_results r
            JOIN interview_invitees i ON i.id = r.interview_invitee_id
            SET r.interviewer_employee_id = i.interviewer_employee_id, r.interviewer_name = i.interviewer_name
        ');
    }

    public function down(): void
    {
        // Gagal bila sudah ada >1 hasil per undangan (hapus yang berlebih dulu).
        Schema::table('interview_results', function (Blueprint $table) {
            $table->unique('interview_invitee_id');
            $table->dropIndex('interview_results_invitee_idx');
            $table->dropColumn(['interviewer_employee_id', 'interviewer_name']);
        });

        Schema::table('interview_schedules', function (Blueprint $table) {
            $table->dropColumn('is_user_interview');
        });
    }
};
