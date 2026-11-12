<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Waktu onboarding (jam), melengkapi onboarding_date yang sebelumnya hanya tanggal. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->time('onboarding_time')->nullable()->after('onboarding_date');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn('onboarding_time');
        });
    }
};
