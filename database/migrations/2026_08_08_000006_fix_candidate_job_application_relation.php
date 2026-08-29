<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK + kolom dari sisi yang salah. dropIndex sebelum dropColumn — kolom dibuat
        // dengan ->index() (migration 065501), SQLite gagal drop kolom yang masih ada indexnya.
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropForeign(['candidate_id']);
            $table->dropIndex(['candidate_id']);
            $table->dropColumn('candidate_id');
        });

        // Tambah FK di sisi yang benar: candidate lahir dari job_application
        Schema::table('candidates', function (Blueprint $table) {
            $table->foreignId('job_application_id')
                ->nullable()
                ->after('user_id')
                ->constrained('job_applications')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['job_application_id']);
            $table->dropColumn('job_application_id');
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->char('candidate_id', 26)->nullable()->index();
            $table->foreign('candidate_id')
                ->references('id')->on('candidates')
                ->nullOnDelete();
        });
    }
};
