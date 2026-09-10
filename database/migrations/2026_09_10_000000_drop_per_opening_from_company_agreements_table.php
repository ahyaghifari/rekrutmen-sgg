<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persetujuan onboarding kembali per-perusahaan saja (general) — tidak ada varian per
 * lowongan. Kolom `per_opening` dibuang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('company_agreements', 'per_opening')) {
            Schema::table('company_agreements', function (Blueprint $table) {
                $table->dropColumn('per_opening');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('company_agreements', 'per_opening')) {
            Schema::table('company_agreements', function (Blueprint $table) {
                $table->boolean('per_opening')->default(false)->after('content');
            });
        }
    }
};
