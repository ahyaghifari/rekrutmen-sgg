<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda "naskah diisi per lowongan": kalau true, kolom `content` di sini dibiarkan kosong
 * ('') dan naskahnya WAJIB diisi saat membuat lowongan (job_opening_agreements dengan judul
 * yang sama, lihat effectiveAgreements()).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('company_agreements', 'per_opening')) {
            Schema::table('company_agreements', function (Blueprint $table) {
                $table->boolean('per_opening')->default(false)->after('content');
            });
        }
    }

    public function down(): void
    {
        Schema::table('company_agreements', function (Blueprint $table) {
            $table->dropColumn('per_opening');
        });
    }
};
