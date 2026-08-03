<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_self_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->enum('satisfaction_level', [
                'very_unsatisfied', 'unsatisfied', 'less_satisfied', 'satisfied', 'very_satisfied',
            ])->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_self_assessments');
    }
};

