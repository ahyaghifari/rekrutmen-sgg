<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Scan STR kandidat nakes (opsional di biodata, bisa menyusul saat onboarding); disalin HRIS jadi dokumen STR saat akun dibuat. */
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('str_file_path')->nullable()->after('str_berlaku_sampai');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', fn (Blueprint $table) => $table->dropColumn('str_file_path'));
    }
};
