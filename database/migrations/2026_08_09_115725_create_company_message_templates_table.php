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
        Schema::create('company_message_templates', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('event', ['screening_rejected', 'screening_passed']);
            $table->string('subject');
            $table->text('body');
            $table->timestamps();

            $table->unique(['company_id', 'event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_message_templates');
    }
};
