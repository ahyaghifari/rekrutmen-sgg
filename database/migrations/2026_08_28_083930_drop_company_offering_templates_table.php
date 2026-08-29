<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ponytail: pivot dari "builder template terstruktur" ke upload/download file bebas —
    // variasi offer per jabatan/kandidat terlalu dinamis buat dipaksa masuk struktur baku.
    public function up(): void
    {
        Schema::dropIfExists('company_offering_templates');
    }

    public function down(): void
    {
        Schema::create('company_offering_templates', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title')->default('OFFERING LETTER');
            $table->string('signature_role_label')->default('Recruiter & Benefit Corporate');
            $table->text('footer_address')->nullable();
            $table->json('compensation_stages');
            $table->json('compensation_rows');
            $table->json('benefits')->nullable();
            $table->json('terms')->nullable();
            $table->timestamps();
        });
    }
};
