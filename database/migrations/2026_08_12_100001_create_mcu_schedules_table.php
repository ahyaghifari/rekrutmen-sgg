<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcu_schedules', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('job_opening_id');
            $table->foreign('job_opening_id')->references('id')->on('job_openings')->cascadeOnDelete();
            $table->date('mcu_date');
            $table->time('mcu_time')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcu_schedules');
    }
};
