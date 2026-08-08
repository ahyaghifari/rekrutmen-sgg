<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('hris_code', 10)->nullable()->unique();
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->string('hero_image_path')->nullable();
            $table->char('brand_color', 7)->default('#042c6c');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
