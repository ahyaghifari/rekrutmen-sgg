<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persetujuan kandidat di-snapshot: judul + naskah disalin ke baris ini saat kandidat
     * menyetujui, jadi perubahan master (company_agreements / job_opening_agreements) tidak
     * mengubah apa yang sudah disetujui. FK master tetap ada untuk pencocokan "sudah setuju".
     * `agreement_type` (konsep lama) dibuang.
     */
    public function up(): void
    {
        Schema::table('candidate_agreements', function (Blueprint $table) {
            $table->string('title')->nullable()->after('candidate_id');
            $table->longText('content')->nullable()->after('title');
        });

        Schema::table('candidate_agreements', function (Blueprint $table) {
            $table->dropUnique(['candidate_id', 'agreement_type']);
            $table->dropColumn('agreement_type');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_agreements', function (Blueprint $table) {
            $table->string('agreement_type')->nullable()->after('candidate_id');
            $table->unique(['candidate_id', 'agreement_type']);
            $table->dropColumn(['title', 'content']);
        });
    }
};
