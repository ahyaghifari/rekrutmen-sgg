<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_application_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->string('field_key', 100);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['job_application_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_application_answers');
    }
};
