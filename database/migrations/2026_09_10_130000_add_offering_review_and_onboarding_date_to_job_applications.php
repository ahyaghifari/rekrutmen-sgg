<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gap offering-letter review + tanggal onboarding.
 *
 * - offering_signed_reviewed_at: recruiter sudah MENERIMA surat bertanda tangan pelamar.
 * - offering_signed_revision_reason: alasan recruiter minta pelamar kirim ulang (terisi =
 *   sedang diminta ulang; dikosongkan lagi begitu pelamar unggah versi baru).
 * - onboarding_date: tanggal onboarding yang ditetapkan recruiter — jadi cikal bakal
 *   Tanggal Bergabung karyawan di HRIS (CandidateOnboardingService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->timestamp('offering_signed_reviewed_at')->nullable()->after('offering_letter_signed_at');
            $table->text('offering_signed_revision_reason')->nullable()->after('offering_signed_reviewed_at');
            $table->date('onboarding_date')->nullable()->after('offering_signed_revision_reason');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'offering_signed_reviewed_at',
                'offering_signed_revision_reason',
                'onboarding_date',
            ]);
        });
    }
};
