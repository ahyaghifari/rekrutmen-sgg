<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcu_lab_results', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('mcu_invitee_id', 26)->unique();
            $table->foreign('mcu_invitee_id')->references('id')->on('mcu_invitees')->cascadeOnDelete();

            foreach ([
                'hemoglobin', 'leukosit', 'eritrosit', 'trombosit', 'hematokrit',
                'basofil', 'eosinofil', 'stab', 'segmen', 'limfosit', 'monosit',
                'mcv', 'mch', 'mchc', 'gds', 'gda',
            ] as $field) {
                $table->string($field)->nullable();
            }

            $table->string('hbsag')->nullable();
            $table->string('hiv')->nullable();
            $table->text('kesimpulan')->nullable();
            $table->string('file_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcu_lab_results');
    }
};
