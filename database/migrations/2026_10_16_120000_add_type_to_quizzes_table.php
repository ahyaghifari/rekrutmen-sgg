<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Jenis tes: `psikotes` (CFIT, dihitung IQ saat export) atau `bidang` (tes bidang, skor biasa). */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('type', 20)->default('bidang')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
