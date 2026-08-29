<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ponytail: konsep "mode pengelolaan" per-company dihapus — gating edit/delete
    // sekarang murni per-lowongan lewat job_openings.hris_job_opening_id (sudah ada).
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('management_mode');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->enum('management_mode', ['free', 'hris'])->default('free')->after('is_active');
        });
    }
};
