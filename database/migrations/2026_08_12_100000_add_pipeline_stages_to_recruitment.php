<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE job_applications MODIFY COLUMN status ENUM('applied','screening','shortlisted','interview','mcu','onboarding','passed','rejected') NOT NULL DEFAULT 'applied'");

        Schema::table('job_applications', function (Blueprint $table) {
            $table->timestamp('quiz_sent_at')->nullable()->after('status');
            $table->enum('quiz_result', ['lulus', 'tidak_lulus'])->nullable()->after('quiz_sent_at');
            $table->timestamp('psychotest_completed_at')->nullable()->after('quiz_result');
            $table->enum('interview_result', ['lulus', 'tidak_lulus'])->nullable()->after('psychotest_completed_at');
        });

        Schema::table('job_openings', function (Blueprint $table) {
            $table->boolean('requires_psychotest')->default(false)->after('is_active');
            $table->unsignedInteger('quiz_duration_minutes')->nullable()->after('requires_psychotest');
            $table->text('quiz_instructions')->nullable()->after('quiz_duration_minutes');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('requires_mcu')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('requires_mcu');
        });

        Schema::table('job_openings', function (Blueprint $table) {
            $table->dropColumn(['requires_psychotest', 'quiz_duration_minutes', 'quiz_instructions']);
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['quiz_sent_at', 'quiz_result', 'psychotest_completed_at', 'interview_result']);
        });

        DB::statement("ALTER TABLE job_applications MODIFY COLUMN status ENUM('applied','screening','shortlisted','interview','passed','rejected') NOT NULL DEFAULT 'applied'");
    }
};
