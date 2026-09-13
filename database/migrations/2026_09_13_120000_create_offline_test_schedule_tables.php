<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal tes offline — mirip interview_schedules/interview_invitees, tapi terikat ke
 * offline_test_id juga (bukan cuma job_opening_id) karena satu lowongan bisa punya lebih
 * dari satu tes offline, tiap tes bisa beda jadwal. Satu kandidat sengaja TIDAK unique
 * per job_application_id (beda dari interview_invitees) — kandidat bisa diundang ke lebih
 * dari satu jadwal kalau lowongannya punya lebih dari satu tes offline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_test_schedules', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('job_opening_id');
            $table->char('offline_test_id', 26);
            $table->foreign('job_opening_id')->references('id')->on('job_openings')->cascadeOnDelete();
            $table->foreign('offline_test_id')->references('id')->on('offline_tests')->cascadeOnDelete();
            $table->date('test_date');
            $table->time('test_time')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
        });

        Schema::create('offline_test_invitees', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('offline_test_schedule_id', 26);
            $table->foreign('offline_test_schedule_id')->references('id')->on('offline_test_schedules')->cascadeOnDelete();
            $table->char('job_application_id', 26);
            $table->foreign('job_application_id')->references('id')->on('job_applications')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_test_invitees');
        Schema::dropIfExists('offline_test_schedules');
    }
};
