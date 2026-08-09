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
        Schema::create('screening_decisions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('job_application_id', 26)->unique();
            $table->foreign('job_application_id')->references('id')->on('job_applications')->cascadeOnDelete();
            $table->enum('decision', ['passed', 'rejected']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screening_decisions');
    }
};
