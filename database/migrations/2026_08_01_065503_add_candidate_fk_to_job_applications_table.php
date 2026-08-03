<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah FK constraint candidate_id → candidates.id.
     * Sengaja dipisah dari create_job_applications karena candidates belum
     * ada saat migration job_applications dijalankan.
     */
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->foreign('candidate_id')
                ->references('id')->on('candidates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropForeign(['candidate_id']);
        });
    }
};
