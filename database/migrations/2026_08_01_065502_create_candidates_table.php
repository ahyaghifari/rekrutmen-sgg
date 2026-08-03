<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profil resmi kandidat — dibuat saat job_applications.status naik jadi
     * "passed" (lolos wawancara). Lihat PRD §4b.
     *
     * PK adalah ULID (sama dengan pola Candidate di HRIS).
     * user_id UNIQUE: 1 orang = 1 profil kandidat, tidak per-lamaran.
     * Lowongan TIDAK disimpan di sini — kandidat bisa lamar lagi; ambil dari
     * jobApplications()->latest()->first()->jobOpening.
     * Field yang sudah ada di job_applications (gender, birth_date, pendidikan, WA)
     * TIDAK diulang di sini — diakses via relasi.
     */
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Pre-filled dari job_applications.full_name, bisa dikoreksi
            $table->string('name');
            $table->string('nickname')->nullable();

            // Identitas resmi (belum ada di job_applications)
            $table->string('nik', 16)->nullable();
            $table->string('no_kk', 16)->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->string('body_height_weight')->nullable();
            $table->string('religion')->nullable();
            $table->string('nationality')->nullable()->default('Indonesia');
            $table->string('status_marital')->nullable();
            $table->date('marriage_date')->nullable();
            $table->string('mother_name')->nullable();

            // Alamat KTP resmi (terstruktur — beda dari job_applications.domicile_address)
            $table->text('ktp_address')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();

            $table->string('social_media_accounts')->nullable();

            // BPJS & rekening
            $table->string('no_bpjskes')->nullable();
            $table->string('faskes')->nullable();
            $table->string('no_bpjstk')->nullable();
            $table->string('company_bpjstk')->nullable();
            $table->string('bsi_bank_number')->nullable();

            $table->text('profile_picture')->nullable();

            // Status internal kandidat (offering → contract → hired, dst.)
            $table->string('overall_status')->nullable()->default('new');
            $table->boolean('is_onboarded')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
