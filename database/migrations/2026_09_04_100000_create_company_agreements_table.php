<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar persetujuan yang ditetapkan tiap perusahaan (kontrak kerja,
     * peraturan perusahaan, tata tertib, kode etik, dst) — disetujui kandidat
     * di tahap TTD & onboarding. Naskah bebas (rich text), dikelola HR di admin.
     */
    public function up(): void
    {
        Schema::create('company_agreements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_agreements');
    }
};
