<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('application_notifications', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('job_application_id', 26);
            $table->foreign('job_application_id')->references('id')->on('job_applications')->cascadeOnDelete();
            $table->enum('event', ['screening_rejected', 'screening_passed']);
            $table->string('subject');
            $table->text('body');
            $table->enum('status', ['draft', 'sent'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['job_application_id', 'event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_notifications');
    }
};
