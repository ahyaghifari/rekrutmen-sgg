<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('relation');
            $table->string('gender')->nullable();
            $table->string('name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('education')->nullable();
            $table->string('job')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_family_members');
    }
};

