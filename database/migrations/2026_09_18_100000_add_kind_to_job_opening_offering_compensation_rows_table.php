<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Diskriminator buat mode LIST (RS) — dua daftar independen "Mitra"/"Kontrak" perlu tanda
     * tegas milik daftar mana, karena baris tanpa nominal (component non-uang, mis. "Seragam
     * Kerja") punya amount_orientasi & amount_kontrak SAMA-SAMA null, jadi tidak bisa dibedakan
     * cuma dari isi kolom nominal (whereNull doang salah nangkep, lihat percobaan 2026-09-18).
     * Null = baris mode TABEL (SGG), dipakai bareng (label + Orientasi + Kontrak sebaris).
     */
    public function up(): void
    {
        Schema::table('job_opening_offering_compensation_rows', function (Blueprint $table) {
            $table->string('kind')->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('job_opening_offering_compensation_rows', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
