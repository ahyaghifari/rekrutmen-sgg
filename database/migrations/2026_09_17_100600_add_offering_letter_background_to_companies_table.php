<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Background full-halaman (letterhead) offering letter, disimpan di disk local (bukan public) — kalau kosong pakai default.jpg. */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('offering_letter_background_path')->nullable()->after('offering_letter_template');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('offering_letter_background_path');
        });
    }
};
