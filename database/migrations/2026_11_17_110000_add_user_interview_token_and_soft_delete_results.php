<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penilaian wawancara user lewat link:
 * - companies.user_interview_token: token di link `/wawancara-user/{token}` (satu per perusahaan, untuk semua lowongan).
 *   Dibuat HRIS saat pertama dibutuhkan; Reset Link menggantinya.
 * - interview_results soft delete: HR bisa menghapus penilaian yang tidak sah tanpa kehilangan jejaknya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('user_interview_token', 64)->nullable()->unique();
        });

        Schema::table('interview_results', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('deleted_by', 26)->nullable(); // ULID user HRIS yang menghapus
        });
    }

    public function down(): void
    {
        Schema::table('interview_results', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'deleted_by']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['user_interview_token']);
            $table->dropColumn('user_interview_token');
        });
    }
};
