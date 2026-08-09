<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->unsignedInteger('max_applicants')->nullable()->after('quota');
            $table->date('applications_deadline')->nullable()->after('max_applicants');
            $table->string('hris_job_opening_id')->nullable()->unique()->after('applications_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->dropUnique(['hris_job_opening_id']);
            $table->dropColumn(['max_applicants', 'applications_deadline', 'hris_job_opening_id']);
        });
    }
};
