<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * job_openings berfungsi sebagai cache lokal read-only agar FK
     * job_applications.job_opening_id punya target yang valid. Bukan sumber
     * kebenaran — untuk sekarang diisi dummy/seed, ke depan disinkron dari
     * API HRIS. Lihat knowledge/PRD.md §4c.
     *
     * PK adalah ULID yang sama dengan id job opening di HRIS, sehingga
     * tidak perlu kolom hris_job_opening_id terpisah.
     */
    public function up(): void
    {
        Schema::create('job_openings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->string('company_name');
            $table->unsignedInteger('quota')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_openings');
    }
};
