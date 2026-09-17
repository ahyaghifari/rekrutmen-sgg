<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penandatangan offering letter (nama, jabatan, gambar TTD) ditetapkan per
     * perusahaan — sama untuk semua surat yang digenerate, bukan diisi ulang
     * tiap kandidat. Catatan bintang dipakai di daftar benefit (mis. "*) akan
     * diberikan jika telah menjadi karyawan tetap").
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('offering_signatory_name')->nullable();
            $table->string('offering_signatory_title')->nullable();
            $table->string('offering_signatory_signature_path')->nullable();
            $table->string('offering_benefit_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'offering_signatory_name',
                'offering_signatory_title',
                'offering_signatory_signature_path',
                'offering_benefit_note',
            ]);
        });
    }
};
