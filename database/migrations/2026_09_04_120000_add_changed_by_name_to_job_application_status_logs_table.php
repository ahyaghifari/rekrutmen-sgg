<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot nama aktor untuk perubahan status yang dilakukan dari LUAR project ini (HRIS,
 * yang mengambil alih admin rekrutmen — DB dipakai bersama). `changed_by` tetap FK ke
 * `users` project ini (diisi kalau aksi jalan di dalam project ini); kalau HRIS yang
 * mengubah, `changed_by` null dan `changed_by_name` diisi nama aktor HRIS (string, tak
 * terhubung ke tabel mana pun).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_application_status_logs', function (Blueprint $table) {
            $table->string('changed_by_name')->nullable()->after('changed_by');
        });
    }

    public function down(): void
    {
        Schema::table('job_application_status_logs', function (Blueprint $table) {
            $table->dropColumn('changed_by_name');
        });
    }
};
