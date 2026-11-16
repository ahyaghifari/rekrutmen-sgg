<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan default per perusahaan (1 baris per perusahaan), dikelola dari Master Perusahaan di HRIS.
 * Kolom pertama: interview_notes = isi awal "Catatan untuk Pelamar" di modal jadwal wawancara.
 * Kolom pengaturan berikutnya ditambah di tabel ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('interview_notes')->nullable();
            $table->timestamps();
        });

        // Default awal RSU Syifa Medika Banjarbaru (hris_code 0001).
        $companyId = DB::table('companies')->where('hris_code', '0001')->value('id');
        if ($companyId) {
            DB::table('company_settings')->insert([
                'company_id' => $companyId,
                'interview_notes' => "⏰ Interview bergantian, pastikan Anda bersedia menunggu\n"
                    . "👔 Pakaian: Bersih, rapi, dan sopan\n"
                    . '📌 Selama proses rekrutmen kami tidak pernah memungut biaya atau bekerjasama dengan pihak mana pun.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
