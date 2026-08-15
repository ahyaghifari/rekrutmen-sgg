<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_opening_quiz', function (Blueprint $table) {
            $table->string('job_opening_id');
            $table->char('quiz_id', 26);
            $table->foreign('job_opening_id')->references('id')->on('job_openings')->cascadeOnDelete();
            $table->foreign('quiz_id')->references('id')->on('quizzes')->cascadeOnDelete();
            $table->primary(['job_opening_id', 'quiz_id']);
        });

        // Pindahkan relasi quiz_id tunggal yang sudah ada ke tabel pivot.
        DB::table('job_openings')->whereNotNull('quiz_id')->get(['id', 'quiz_id'])->each(function ($row) {
            DB::table('job_opening_quiz')->insert([
                'job_opening_id' => $row->id,
                'quiz_id'        => $row->quiz_id,
            ]);
        });

        Schema::table('job_openings', function (Blueprint $table) {
            $table->dropForeign(['quiz_id']);
            $table->dropColumn('quiz_id');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropForeign(['job_application_id']);
            $table->dropUnique(['job_application_id']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->char('quiz_id', 26)->nullable()->after('job_application_id');
            $table->foreign('quiz_id')->references('id')->on('quizzes')->cascadeOnDelete();
            $table->foreign('job_application_id')->references('id')->on('job_applications')->cascadeOnDelete();
        });

        // Backfill quiz_id di attempt yang sudah ada dari relasi lowongan->kuis (dulu cuma 1 kuis/lowongan).
        DB::statement('
            UPDATE quiz_attempts qa
            JOIN job_applications ja ON ja.id = qa.job_application_id
            JOIN job_opening_quiz joq ON joq.job_opening_id = ja.job_opening_id
            SET qa.quiz_id = joq.quiz_id
            WHERE qa.quiz_id IS NULL
        ');

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->unique(['job_application_id', 'quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropForeign(['job_application_id']);
            $table->dropForeign(['quiz_id']);
            $table->dropUnique(['job_application_id', 'quiz_id']);
            $table->dropColumn('quiz_id');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreign('job_application_id')->references('id')->on('job_applications')->cascadeOnDelete();
            $table->unique('job_application_id');
        });

        Schema::table('job_openings', function (Blueprint $table) {
            $table->char('quiz_id', 26)->nullable();
            $table->foreign('quiz_id')->references('id')->on('quizzes')->nullOnDelete();
        });

        DB::table('job_opening_quiz')->orderBy('job_opening_id')->get()->groupBy('job_opening_id')->each(function ($rows, $jobOpeningId) {
            DB::table('job_openings')->where('id', $jobOpeningId)->update(['quiz_id' => $rows->first()->quiz_id]);
        });

        Schema::dropIfExists('job_opening_quiz');
    }
};
