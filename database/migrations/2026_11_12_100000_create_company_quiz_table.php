<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kuis default per perusahaan: otomatis terpilih saat membuat lowongan baru di perusahaan itu
 * (diatur dari panel rekrutmen di HRIS). Lowongan yang sudah ada tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_quiz', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->char('quiz_id', 26);
            $table->foreign('quiz_id')->references('id')->on('quizzes')->cascadeOnDelete();
            $table->primary(['company_id', 'quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_quiz');
    }
};
