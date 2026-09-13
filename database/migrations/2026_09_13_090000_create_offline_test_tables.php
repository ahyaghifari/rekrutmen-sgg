<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tes offline — berlaku hanya untuk lowongan yang meng-assign-nya lewat pivot
 * job_opening_offline_test (mirip quizzes/job_opening_quiz). Beda dari quiz: tidak ada
 * soal/pengerjaan di sistem, nilai & lulus/tidak dicatat manual per kandidat di
 * offline_test_results.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_tests', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('job_opening_offline_test', function (Blueprint $table) {
            $table->string('job_opening_id');
            $table->char('offline_test_id', 26);
            $table->foreign('job_opening_id')->references('id')->on('job_openings')->cascadeOnDelete();
            $table->foreign('offline_test_id')->references('id')->on('offline_tests')->cascadeOnDelete();
            $table->primary(['job_opening_id', 'offline_test_id']);
        });

        Schema::create('offline_test_results', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('job_application_id', 26);
            $table->char('offline_test_id', 26);
            $table->decimal('score', 5, 2)->nullable();
            $table->string('result')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->foreign('job_application_id')->references('id')->on('job_applications')->cascadeOnDelete();
            $table->foreign('offline_test_id')->references('id')->on('offline_tests')->cascadeOnDelete();
            $table->unique(['job_application_id', 'offline_test_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_test_results');
        Schema::dropIfExists('job_opening_offline_test');
        Schema::dropIfExists('offline_tests');
    }
};
