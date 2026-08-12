<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcu_invitees', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('mcu_schedule_id', 26);
            $table->foreign('mcu_schedule_id')->references('id')->on('mcu_schedules')->cascadeOnDelete();
            $table->char('job_application_id', 26)->unique();
            $table->foreign('job_application_id')->references('id')->on('job_applications')->cascadeOnDelete();
            $table->enum('result', ['lulus', 'tidak_lulus'])->nullable();
            $table->text('health_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcu_invitees');
    }
};
