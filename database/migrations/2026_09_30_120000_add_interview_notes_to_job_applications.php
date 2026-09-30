<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan hasil wawancara, diisi HR bersamaan dengan keputusan lulus/tidak lulus (permintaan user
     * 2026-09-30). Isinya HTML dari rich text editor, makanya text dan bukan varchar.
     *
     * Ditaruh di job_applications, sebaris dengan interview_result: catatan ini milik KEPUTUSAN,
     * bukan milik sesi wawancara tertentu - sama seperti hasilnya.
     */
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->text('interview_notes')->nullable()->after('interview_result');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn('interview_notes');
        });
    }
};
