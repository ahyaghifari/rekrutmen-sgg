<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('job_opening_id');
            $table->foreign('job_opening_id')->references('id')->on('job_openings')->cascadeOnDelete();
            $table->text('question');
            $table->json('options');
            $table->string('correct_option');
            $table->unsignedInteger('points')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('job_application_id', 26)->unique();
            $table->foreign('job_application_id')->references('id')->on('job_applications')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('deadline_at');
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->enum('status', ['in_progress', 'submitted', 'expired'])->default('in_progress');
            $table->timestamps();
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('quiz_attempt_id', 26);
            $table->foreign('quiz_attempt_id')->references('id')->on('quiz_attempts')->cascadeOnDelete();
            $table->char('quiz_question_id', 26);
            $table->foreign('quiz_question_id')->references('id')->on('quiz_questions')->cascadeOnDelete();
            $table->string('selected_option')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
    }
};
