<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Rekrutmen dokter umum per rumah sakit sifatnya buka/tutup, bukan lowongan. */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('dokter_umum_open')->default(false)->after('is_hospital');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('dokter_umum_open');
        });
    }
};
