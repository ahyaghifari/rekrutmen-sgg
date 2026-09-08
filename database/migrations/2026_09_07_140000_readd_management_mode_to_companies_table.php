<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mode pengelolaan rekrutmen per perusahaan (dulu ada, dihapus 2026-08-27, dipakai lagi):
     * 'hris' = lowongan & pipeline dikelola sepenuhnya di HRIS, admin rekrutmen read-only.
     * 'free' = admin rekrutmen boleh menambah/mengubah/menghapus lowongan sendiri.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'management_mode')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->enum('management_mode', ['free', 'hris'])->default('hris')->after('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('management_mode');
        });
    }
};
