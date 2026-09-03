<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offering_negotiations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('job_application_id')->constrained()->cascadeOnDelete();
            $table->enum('sender', ['applicant', 'recruiter']);
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offering_negotiations');
    }
};
