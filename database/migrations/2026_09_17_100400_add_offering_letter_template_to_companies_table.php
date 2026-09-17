<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Gaya visual offering letter (lihat App\Enums\OfferingLetterTemplate) — beberapa perusahaan bisa pakai gaya yang sama. */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('offering_letter_template')->default('sgg')->after('offering_benefit_note');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('offering_letter_template');
        });
    }
};
