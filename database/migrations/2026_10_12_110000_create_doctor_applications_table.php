<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Satu akun dokter = satu lamaran (unique user_id). data = isian form, files = berkas yang sudah diunggah. */
    public function up(): void
    {
        Schema::create('doctor_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('jenis', 20); // umum | spesialis
            $table->foreignId('company_id')->constrained();
            $table->string('unit_minat')->nullable();
            $table->string('status', 30)->default('baru');
            $table->json('data');
            $table->json('files')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_applications');
    }
};
