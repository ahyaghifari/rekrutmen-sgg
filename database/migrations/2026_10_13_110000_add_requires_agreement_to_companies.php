<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perusahaan yang memakai persetujuan onboarding. Default false: kandidat langsung mengisi Data Diri,
     * persetujuan diaktifkan per perusahaan. Perusahaan yang SUDAH punya naskah persetujuan (milik
     * perusahaan, atau milik lowongannya) tetap true supaya perilakunya tidak berubah diam-diam.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('requires_agreement')->default(false)->after('requires_mcu');
        });

        DB::table('companies')->where(function ($q) {
            $q->whereIn('id', DB::table('company_agreements')->select('company_id'))
              ->orWhereIn('id', DB::table('job_openings')
                  ->whereIn('id', DB::table('job_opening_agreements')->select('job_opening_id'))
                  ->select('company_id'));
        })->update(['requires_agreement' => true]);
    }

    public function down(): void
    {
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn('requires_agreement'));
    }
};
