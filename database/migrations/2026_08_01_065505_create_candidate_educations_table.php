<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat pendidikan terstruktur â€” beda dari job_applications.education_level
     * yang hanya jenjang terakhir. Di sini bisa multi-baris (SMA â†’ D3 â†’ S1).
     */
    public function up(): void
    {
        Schema::create('candidate_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('level');
            $table->string('school_name')->nullable();
            $table->string('major')->nullable();
            $table->decimal('gpa', 4, 2)->nullable();
            $table->year('start_year')->nullable();
            $table->year('end_year')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_educations');
    }
};

