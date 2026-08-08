<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // 'free' = kelola lowongan langsung di sistem ini
            // 'hris' = lowongan dikirim dari sistem HRIS eksternal
            $table->enum('management_mode', ['free', 'hris'])->default('free')->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('management_mode');
        });
    }
};
