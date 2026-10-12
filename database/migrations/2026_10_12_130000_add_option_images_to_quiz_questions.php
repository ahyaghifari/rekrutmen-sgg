<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Peta label pilihan => path gambar, mis. {"A": "quiz-options/x.png"}. Label tetap jadi nilai jawaban yang dinilai. */
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->json('option_images')->nullable()->after('options');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn('option_images');
        });
    }
};
