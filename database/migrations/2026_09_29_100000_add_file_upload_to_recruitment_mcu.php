<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MCU rekrutmen dipisah dari MCU Sikartap (keputusan user 2026-09-29): di rekrutmen semua
     * stasiun cukup UNGGAH BERKAS hasilnya, tidak lagi mengisi rincian per pemeriksaan. Dokter
     * ikut mengunggah berkas tapi tetap memilih rekomendasi fit / tidak fit; keputusan akhir
     * tetap milik HRD lewat mcu_invitees.status_mcu.
     *
     * Laboratorium & radiologi sudah punya file_path sejak awal, jadi yang ditambah cuma perawat,
     * dokter, dan kolom rekomendasi dokternya.
     *
     * Kolom rincian yang lama (tinggi_badan, hemoglobin, visus_mata, ...) SENGAJA TIDAK dihapus:
     * data MCU yang sudah terisi sebelum perubahan ini harus tetap terbaca di halaman Hasil MCU,
     * dan tabel MCU Sikartap yang bentuknya masih sama tidak ikut terpengaruh.
     */
    public function up(): void
    {
        Schema::table('mcu_nurse_assessments', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('catatan');
        });

        Schema::table('mcu_doctor_examinations', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('catatan');
            $table->enum('rekomendasi_fit', ['fit', 'unfit'])->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('mcu_nurse_assessments', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });

        Schema::table('mcu_doctor_examinations', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'rekomendasi_fit']);
        });
    }
};
