<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persetujuan digital kandidat untuk kontrak/peraturan/tata tertib.
     * Cukup consent record (checkbox + timestamp + IP) untuk sekarang â€”
     * kekuatan hukum masih pending klarifikasi. Lihat PRD Â§3 & Â§8.
     */
    public function up(): void
    {
        Schema::create('candidate_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('agreement_type'); // kontrak / peraturan / tata_tertib
            $table->timestamp('agreed_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'agreement_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_agreements');
    }
};

