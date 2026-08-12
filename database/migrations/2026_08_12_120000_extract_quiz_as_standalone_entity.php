<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quiz jadi entitas mandiri (bisa dipakai berkali-kali di banyak lowongan),
     * bukan lagi 1:1 nempel ke job_openings.
     */
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamps();
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->char('quiz_id', 26)->nullable()->after('id');
            $table->foreign('quiz_id')->references('id')->on('quizzes')->cascadeOnDelete();
        });
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropForeign(['job_opening_id']);
            $table->dropColumn('job_opening_id');
        });

        Schema::table('job_openings', function (Blueprint $table) {
            $table->char('quiz_id', 26)->nullable()->after('requires_psychotest');
            $table->foreign('quiz_id')->references('id')->on('quizzes')->nullOnDelete();
            $table->dropColumn(['quiz_duration_minutes', 'quiz_instructions']);
        });
    }

    public function down(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->dropForeign(['quiz_id']);
            $table->dropColumn('quiz_id');
            $table->unsignedInteger('quiz_duration_minutes')->nullable();
            $table->text('quiz_instructions')->nullable();
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('job_opening_id')->nullable();
            $table->foreign('job_opening_id')->references('id')->on('job_openings')->cascadeOnDelete();
            $table->dropForeign(['quiz_id']);
            $table->dropColumn('quiz_id');
        });

        Schema::dropIfExists('quizzes');
    }
};
