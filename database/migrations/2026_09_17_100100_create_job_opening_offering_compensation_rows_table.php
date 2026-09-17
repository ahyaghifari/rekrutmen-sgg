<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar baris komponen kompensasi offering letter PER LOWONGAN (bukan per perusahaan
     * lagi — komponen & nominalnya beda-beda tiap posisi, mis. dokter vs admin di company
     * yang sama). Nilai rupiah (Orientasi / Kontrak) diisi di sini juga, bukan diketik ulang
     * manual tiap kandidat saat surat digenerate seperti versi awal.
     */
    public function up(): void
    {
        Schema::create('job_opening_offering_compensation_rows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('job_opening_id')->constrained('job_openings')->cascadeOnDelete();
            $table->string('label');
            $table->unsignedBigInteger('amount_orientasi')->default(0);
            $table->unsignedBigInteger('amount_kontrak')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_opening_offering_compensation_rows');
    }
};
