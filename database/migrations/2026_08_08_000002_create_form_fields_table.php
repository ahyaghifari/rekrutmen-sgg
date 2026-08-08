<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_section_id')->constrained('form_sections')->cascadeOnDelete();
            $table->string('name');           // key untuk extra_data (e.g. "nik", "tinggi_badan")
            $table->string('label');          // label tampil ke user
            $table->string('type', 20)->default('text'); // text|textarea|number|date|select|radio|file
            $table->json('options')->nullable(); // select/radio: {"choices":["A","B"]} | file: {"max_kb":5120,"mimes":["pdf"]}
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['form_section_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
