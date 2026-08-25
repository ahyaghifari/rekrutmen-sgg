<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcu_nurse_assessments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('mcu_invitee_id', 26)->unique();
            $table->foreign('mcu_invitee_id')->references('id')->on('mcu_invitees')->cascadeOnDelete();

            $table->decimal('tinggi_badan', 5, 1);
            $table->decimal('berat_badan', 5, 1);
            $table->string('tekanan_darah');
            $table->unsignedSmallInteger('nadi');
            $table->decimal('suhu', 4, 1);
            $table->unsignedSmallInteger('pernafasan');

            $table->text('keluhan_kesehatan')->nullable();

            $table->boolean('rpk_hipertensi')->default(false);
            $table->boolean('rpk_stroke')->default(false);
            $table->boolean('rpk_jantung')->default(false);
            $table->boolean('rpk_diabetes')->default(false);
            $table->string('rpk_lainnya')->nullable();

            $table->text('riwayat_penyakit_dahulu')->nullable();
            $table->text('riwayat_operasi')->nullable();

            $table->boolean('alergi_makanan')->default(false);
            $table->boolean('alergi_obat')->default(false);
            $table->boolean('alergi_debu')->default(false);
            $table->string('alergi_lainnya')->nullable();

            $table->boolean('kebiasaan_olahraga')->default(false);
            $table->boolean('kebiasaan_merokok')->default(false);
            $table->boolean('kebiasaan_alkohol')->default(false);
            $table->boolean('kebiasaan_obat')->default(false);
            $table->string('kebiasaan_lainnya')->nullable();

            $table->string('hasil_ekg')->nullable();
            $table->text('keterangan_ekg')->nullable();
            $table->string('file_ekg')->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcu_nurse_assessments');
    }
};
