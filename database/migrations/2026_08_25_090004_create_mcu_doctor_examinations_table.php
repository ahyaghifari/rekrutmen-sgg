<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcu_doctor_examinations', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('mcu_invitee_id', 26)->unique();
            $table->foreign('mcu_invitee_id')->references('id')->on('mcu_invitees')->cascadeOnDelete();

            foreach ([
                'kulit', 'rambut', 'visus_mata', 'buta_warna', 'lapang_pandang',
                'hidung', 'telinga', 'tonsil', 'faring', 'gigi',
                'kelenjar_gondok',
                'suara_pernafasan', 'ronchi', 'wheezing', 'lainnya_paru', 'jantung',
                'hati', 'limpa', 'epigastrium', 'ginjal', 'lainnya_abdomen',
                'ekstremitas', 'neurologi',
            ] as $field) {
                $table->string($field)->nullable();
            }

            $table->text('diagnosa')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcu_doctor_examinations');
    }
};
