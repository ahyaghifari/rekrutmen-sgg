<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_opening_stage_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('job_opening_id');
            $table->foreign('job_opening_id')->references('id')->on('job_openings')->cascadeOnDelete();
            $table->string('from_status')->nullable(); // null = awal
            $table->string('to_status');
            $table->unsignedInteger('count');          // berapa orang yang berpindah
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('done_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_opening_stage_logs');
    }
};
