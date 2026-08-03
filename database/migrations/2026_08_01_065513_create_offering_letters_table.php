<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Offering letter per lamaran (dikirim saat kandidat lolos wawancara).
     * Mekanisme pengiriman (email otomatis vs PDF manual) masih pending — lihat PRD §8.
     */
    public function up(): void
    {
        Schema::create('offering_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->text('content')->nullable();
            $table->enum('status', ['draft', 'sent', 'viewed'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offering_letters');
    }
};
