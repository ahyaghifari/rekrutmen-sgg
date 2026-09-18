<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nominal kompensasi bukan selalu angka bulat (bisa range "Rp 5jt - Rp 7jt", "Sesuai
     * kesepakatan", atau kosong sama sekali buat baris non-uang kayak "Seragam Kerja",
     * "BPJS Kesehatan") — ganti dari unsignedBigInteger ke string bebas, nullable.
     */
    public function up(): void
    {
        Schema::table('job_opening_offering_compensation_rows', function (Blueprint $table) {
            $table->string('amount_orientasi')->nullable()->default(null)->change();
            $table->string('amount_kontrak')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('job_opening_offering_compensation_rows', function (Blueprint $table) {
            $table->unsignedBigInteger('amount_orientasi')->default(0)->change();
            $table->unsignedBigInteger('amount_kontrak')->default(0)->change();
        });
    }
};
