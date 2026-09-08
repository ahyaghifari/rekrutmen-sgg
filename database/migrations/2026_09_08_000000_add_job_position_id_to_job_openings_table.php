<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tautan lowongan ke jabatan HRIS (m_job_positions.id, ULID). Diisi dari form Lowongan
     * di admin HRIS untuk perusahaan mode 'free' — dipakai saat onboarding kandidat 'passed'
     * jadi karyawan HRIS tanpa perlu hris_job_opening_id. Tanpa FK: target ada di DB HRIS.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('job_openings', 'job_position_id')) {
            Schema::table('job_openings', function (Blueprint $table) {
                $table->char('job_position_id', 26)->nullable()->after('company_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->dropColumn('job_position_id');
        });
    }
};
