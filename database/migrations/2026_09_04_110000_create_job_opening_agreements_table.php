<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persetujuan khusus per jabatan/lowongan. Kalau judulnya sama dengan
     * salah satu persetujuan perusahaan (company_agreements), versi jabatan ini
     * MENIMPA versi perusahaan untuk kandidat lowongan tsb; kalau judulnya baru,
     * dia jadi persetujuan tambahan. Contoh: "Jadwal Absensi" beda per jabatan,
     * tapi "Peraturan Perusahaan" cukup di level perusahaan.
     */
    public function up(): void
    {
        if (! Schema::hasTable('job_opening_agreements')) {
            Schema::create('job_opening_agreements', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->foreignUlid('job_opening_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->longText('content');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('candidate_agreements', function (Blueprint $table) {
            if (! Schema::hasColumn('candidate_agreements', 'job_opening_agreement_id')) {
                $table->foreignUlid('job_opening_agreement_id')->nullable()->after('company_agreement_id')
                    ->constrained('job_opening_agreements')->cascadeOnDelete();
            }
            $table->unique(['candidate_id', 'job_opening_agreement_id'], 'cand_agree_joa_unique');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_agreements', function (Blueprint $table) {
            $table->dropUnique('cand_agree_joa_unique');
            $table->dropConstrainedForeignId('job_opening_agreement_id');
        });
        Schema::dropIfExists('job_opening_agreements');
    }
};
