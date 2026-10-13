<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * job_openings.is_nakes: salinan JobPositions::is_nakes HRIS (project ini tidak punya koneksi ke
     * DB HRIS), diisi HRIS saat lowongan/jabatan disimpan - sama seperti division_name.
     * candidates.no_str: nomor STR, hanya diminta kalau jabatan yang dilamar nakes.
     */
    public function up(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->boolean('is_nakes')->default(false)->after('division_name');
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->string('no_str', 100)->nullable()->after('mother_name');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', fn (Blueprint $table) => $table->dropColumn('no_str'));
        Schema::table('job_openings', fn (Blueprint $table) => $table->dropColumn('is_nakes'));
    }
};
