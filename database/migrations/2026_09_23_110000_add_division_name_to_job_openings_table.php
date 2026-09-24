<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Divisi dititipkan dari HRIS saat lowongan dibuat/diubah (JobOpeningResource mengisinya dari
     * JobPositions->division), bukan dilihat langsung ke master jabatan — project ini tidak punya
     * koneksi ke database HRIS sama sekali. Dipakai token {divisi} di naskah Persetujuan Onboarding,
     * lihat JobOpening::effectiveAgreements().
     */
    public function up(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->string('division_name')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->dropColumn('division_name');
        });
    }
};
