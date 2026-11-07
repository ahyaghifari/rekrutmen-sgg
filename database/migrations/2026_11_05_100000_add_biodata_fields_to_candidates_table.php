<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Biodata pelamar (form docx): semua kolom nullable, aditif — tabel ini dibaca HRIS. */
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('gender', 10)->nullable()->after('nickname');
            $table->string('birth_place')->nullable()->after('gender');
            $table->date('birth_date')->nullable()->after('birth_place');
            $table->string('emergency_contact_name')->nullable()->after('mother_name');
            $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name');
            $table->timestamp('biodata_confirmed_at')->nullable()->after('is_onboarded');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn(['gender', 'birth_place', 'birth_date', 'emergency_contact_name', 'emergency_contact_phone', 'biodata_confirmed_at']);
        });
    }
};
