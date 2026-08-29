<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE job_applications MODIFY COLUMN status ENUM('applied','screening','shortlisted','interview','offering','mcu','onboarding','passed','rejected') NOT NULL DEFAULT 'applied'");

        Schema::table('job_applications', function (Blueprint $table) {
            $table->timestamp('offering_letter_generated_at')->nullable()->after('interview_result');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn('offering_letter_generated_at');
        });

        DB::statement("ALTER TABLE job_applications MODIFY COLUMN status ENUM('applied','screening','shortlisted','interview','mcu','onboarding','passed','rejected') NOT NULL DEFAULT 'applied'");
    }
};
