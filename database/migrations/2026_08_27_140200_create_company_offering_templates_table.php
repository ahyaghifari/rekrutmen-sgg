<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_offering_templates', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('title')->default('OFFERING LETTER');
            $table->string('signature_role_label')->default('Recruiter & Benefit Corporate');
            $table->text('footer_address')->nullable();

            // Struktur tabel kompensasi: nama tahap kerja (mis. "Orientasi (3 bulan)", "PKWT")
            // dan nama baris komponen (mis. "Gaji Pokok", "Tunjangan Jabatan") — nilai rupiahnya
            // diisi per kandidat saat surat dibuat, bukan bagian dari template.
            $table->json('compensation_stages');
            $table->json('compensation_rows');

            $table->json('benefits')->nullable();
            $table->json('terms')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_offering_templates');
    }
};
