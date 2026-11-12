<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Interviewer pindah dari interview_results ke interview_invitees: yang mewawancarai adalah bagian dari
 * undangan sesi (pelamar + sesi), bukan dari hasil penilaiannya. Nilai yang sudah terisi ikut disalin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_invitees', function (Blueprint $table) {
            // ULID karyawan HRIS (beda database, tanpa FK) + salinan nama saat dipilih.
            $table->string('interviewer_employee_id', 26)->nullable();
            $table->string('interviewer_name')->nullable();
        });

        DB::statement('
            UPDATE interview_invitees i
            JOIN interview_results r ON r.interview_invitee_id = i.id
            SET i.interviewer_employee_id = r.interviewer_employee_id, i.interviewer_name = r.interviewer_name
        ');

        Schema::table('interview_results', function (Blueprint $table) {
            $table->dropColumn(['interviewer_employee_id', 'interviewer_name']);
        });
    }

    public function down(): void
    {
        Schema::table('interview_results', function (Blueprint $table) {
            $table->string('interviewer_employee_id', 26)->nullable();
            $table->string('interviewer_name')->nullable();
        });

        DB::statement('
            UPDATE interview_results r
            JOIN interview_invitees i ON i.id = r.interview_invitee_id
            SET r.interviewer_employee_id = i.interviewer_employee_id, r.interviewer_name = i.interviewer_name
        ');

        Schema::table('interview_invitees', function (Blueprint $table) {
            $table->dropColumn(['interviewer_employee_id', 'interviewer_name']);
        });
    }
};
