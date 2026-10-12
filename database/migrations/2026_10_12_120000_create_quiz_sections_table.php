<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Subtest dalam satu kuis (mis. psikotes: Series, Classification, Matrices). Kuis tanpa subtest tetap jalan: quiz_section_id null. */
    public function up(): void
    {
        Schema::create('quiz_sections', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('quiz_id', 26);
            $table->foreign('quiz_id')->references('id')->on('quizzes')->cascadeOnDelete();
            $table->string('name');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->char('quiz_section_id', 26)->nullable()->after('quiz_id');
            $table->foreign('quiz_section_id')->references('id')->on('quiz_sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropForeign(['quiz_section_id']);
            $table->dropColumn('quiz_section_id');
        });

        Schema::dropIfExists('quiz_sections');
    }
};
