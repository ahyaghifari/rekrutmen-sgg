<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alamat offering letter dipecah terstruktur (sama seperti candidates.village/district/
     * city/province) — bukan cuma satu textarea bebas — supaya begitu kandidat naik status
     * ke onboarding/passed, Candidate::firstOrCreate() (JobApplication::booted()) bisa isi
     * ktp_address/village/district/city/province otomatis dari sini. Kandidat tidak perlu
     * ngetik alamat dua kali (offering letter, lalu data diri).
     */
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('offering_village')->nullable()->after('offering_domicile_address');
            $table->string('offering_district')->nullable()->after('offering_village');
            $table->string('offering_city')->nullable()->after('offering_district');
            $table->string('offering_province')->nullable()->after('offering_city');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['offering_village', 'offering_district', 'offering_city', 'offering_province']);
        });
    }
};
