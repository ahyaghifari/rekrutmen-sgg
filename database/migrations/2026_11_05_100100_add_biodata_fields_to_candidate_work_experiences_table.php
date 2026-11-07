<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** `salary` = gaji terakhir (sudah dipakai form lama); `salary_start` = gaji awal. Dua kolom teks hanya diisi pada baris pekerjaan sekarang/terakhir. */
    public function up(): void
    {
        Schema::table('candidate_work_experiences', function (Blueprint $table) {
            $table->decimal('salary_start', 15, 2)->nullable()->after('salary');
            $table->text('job_description')->nullable()->after('is_current');
            $table->text('org_position_description')->nullable()->after('job_description');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_work_experiences', function (Blueprint $table) {
            $table->dropColumn(['salary_start', 'job_description', 'org_position_description']);
        });
    }
};
