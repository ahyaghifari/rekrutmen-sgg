<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kandidat mengonfirmasi/mengisi ulang data ini sendiri di dashboard saat status
     * offering — dipakai buat isi header offering letter, karena field form lamaran
     * dinamis per perusahaan (field_key-nya gak dijamin selalu sama).
     */
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('offering_full_name')->nullable();
            $table->string('offering_birth_place')->nullable();
            $table->date('offering_birth_date')->nullable();
            $table->text('offering_domicile_address')->nullable();
            $table->timestamp('offering_data_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'offering_full_name',
                'offering_birth_place',
                'offering_birth_date',
                'offering_domicile_address',
                'offering_data_confirmed_at',
            ]);
        });
    }
};
