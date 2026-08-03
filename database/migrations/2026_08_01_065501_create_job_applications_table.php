<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Form lamaran pelamar — pengganti Google Form. Field 1:1 dengan Google
     * Form lama (knowledge/PRD.md §4a). Email tidak disimpan di sini, diakses
     * lewat relasi user_id -> users.email.
     *
     * Catatan:
     * - candidate_id nullable, baru diisi saat status naik jadi "passed"
     *   (kandidat lolos wawancara). Kolom ini sengaja dibuat tanpa FK constraint
     *   di migration ini karena tabel `candidates` belum dibuat; FK ditambahkan
     *   di migration create_candidates_table.
     * - Aturan "1 user = 1 lamaran aktif" di-enforce di level aplikasi, bukan
     *   constraint DB (MySQL tidak mendukung partial unique index yang dibutuhkan).
     */
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('job_opening_id')->constrained('job_openings')->restrictOnDelete();
            $table->char('candidate_id', 26)->nullable()->index();

            // Field 1:1 dengan Google Form (lihat PRD §4a untuk urutan asli)
            $table->string('full_name');
            $table->enum('gender', ['laki_laki', 'perempuan']);
            $table->string('whatsapp_number');
            $table->unsignedInteger('age');
            $table->text('domicile_address');
            $table->string('birth_place')->nullable();
            $table->date('birth_date');
            $table->string('instagram_handle');
            $table->string('linkedin_url')->nullable();
            $table->enum('education_level', ['sma_smk', 'd3', 'd4', 's1', 's2', 'profesi']);
            $table->string('major');
            $table->string('school_name');
            $table->year('graduation_year');
            $table->date('str_valid_until')->nullable();
            $table->string('other_position_note')->nullable();
            $table->text('work_experience_note')->nullable();
            $table->boolean('willing_relocate')->default(false);
            $table->decimal('last_salary', 12, 2);
            $table->decimal('expected_salary', 12, 2);
            $table->enum('availability', ['immediately', '2_weeks', '1_month', 'more_than_2_months']);
            $table->string('certificate_file_path')->nullable();
            $table->string('cv_file_path');

            // Status tracking — menggantikan filter manual HC
            $table->enum('status', ['applied', 'screening', 'interview', 'passed', 'rejected'])->default('applied');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
