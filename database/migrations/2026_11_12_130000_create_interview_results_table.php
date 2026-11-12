<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil / penilaian wawancara PER SESI (satu baris per undangan sesi), diisi HR memakai Form Interview.
 *
 * `aspects` = SNAPSHOT aspek saat disimpan: [{aspect_id, name, description, score (1-4), note}], supaya
 * perubahan master (interview_form_aspects) sesudahnya tidak mengubah penilaian yang sudah tersimpan.
 * Nilai: 1 Kurang, 2 Cukup, 3 Baik, 4 Sangat Baik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_results', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('interview_invitee_id', 26)->unique();
            $table->foreign('interview_invitee_id')->references('id')->on('interview_invitees')->cascadeOnDelete();

            // Interviewer = karyawan HRIS (ULID employees.id; beda database, jadi tanpa FK) + salinan nama.
            $table->string('interviewer_employee_id', 26)->nullable();
            $table->string('interviewer_name')->nullable();

            $table->date('interview_date')->nullable();
            $table->json('aspects');
            $table->unsignedSmallInteger('total_score')->default(0);
            $table->unsignedSmallInteger('max_score')->default(0);
            $table->decimal('average_score', 3, 2)->default(0);

            $table->enum('conclusion', ['disarankan', 'dipertimbangkan', 'tidak_disarankan']);
            $table->text('notes')->nullable();
            $table->string('filled_by', 26)->nullable(); // ULID user HRIS yang mengisi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_results');
    }
};
