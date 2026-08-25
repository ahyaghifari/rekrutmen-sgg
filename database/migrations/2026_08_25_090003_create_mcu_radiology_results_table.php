<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcu_radiology_results', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('mcu_invitee_id', 26)->unique();
            $table->foreign('mcu_invitee_id')->references('id')->on('mcu_invitees')->cascadeOnDelete();

            $table->string('jenis_pemeriksaan')->nullable();
            $table->string('foto_thorax')->nullable();
            $table->string('jantung')->nullable();
            $table->string('paru_kanan')->nullable();
            $table->string('paru_kiri')->nullable();
            $table->string('diafragma')->nullable();
            $table->string('tulang')->nullable();
            $table->text('kesimpulan')->nullable();
            $table->string('file_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcu_radiology_results');
    }
};
