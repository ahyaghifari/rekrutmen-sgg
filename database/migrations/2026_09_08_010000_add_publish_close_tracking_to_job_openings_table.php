<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak siklus lowongan: kapan pertama dipublish, kapan ditutup, dan SIAPA (disimpan mentah
 * sebagai nama — pelaku aksi ini user HRIS, id-nya beda dengan tabel `users` di sini, jadi
 * nama string biar dua database sama-sama bisa pakai tanpa FK).
 * Kolom `created_by` bigint FK lama dibiarkan — `created_by_name` yang dipakai lintas DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            if (! Schema::hasColumn('job_openings', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('job_openings', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('published_at');
            }
            if (! Schema::hasColumn('job_openings', 'created_by_name')) {
                $table->string('created_by_name')->nullable()->after('closed_at');
            }
            if (! Schema::hasColumn('job_openings', 'published_by')) {
                $table->string('published_by')->nullable()->after('created_by_name');
            }
            if (! Schema::hasColumn('job_openings', 'closed_by')) {
                $table->string('closed_by')->nullable()->after('published_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->dropColumn(['published_at', 'closed_at', 'created_by_name', 'published_by', 'closed_by']);
        });
    }
};
