<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Batas fitur negosiasi offering berdasarkan pangkat jabatan:
 *  - companies.negotiation_min_pangkat_level: level pangkat MINIMAL (angka, makin tinggi = makin senior) yang boleh
 *    negosiasi. NULL = belum diatur -> negosiasi mati untuk lowongan baru.
 *  - job_openings.can_negotiation: negosiasi aktif untuk lowongan ini. Bawaan baris BARU false; nilai awalnya
 *    dihitung dari aturan di atas oleh HRIS saat lowongan dibuat. Baris yang SUDAH ada diisi true supaya pelamar
 *    yang sedang berproses tidak tiba-tiba kehilangan fitur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedSmallInteger('negotiation_min_pangkat_level')->nullable();
        });

        Schema::table('job_openings', function (Blueprint $table) {
            $table->boolean('can_negotiation')->default(false);
        });

        DB::table('job_openings')->update(['can_negotiation' => true]);
    }

    public function down(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->dropColumn('can_negotiation');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('negotiation_min_pangkat_level');
        });
    }
};
