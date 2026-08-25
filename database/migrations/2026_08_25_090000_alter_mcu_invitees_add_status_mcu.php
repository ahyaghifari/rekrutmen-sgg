<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mcu_invitees', function (Blueprint $table) {
            $table->dropColumn(['result', 'health_notes']);
        });

        Schema::table('mcu_invitees', function (Blueprint $table) {
            $table->enum('status_mcu', ['pending', 'fit', 'unfit'])->default('pending')->after('job_application_id');
            $table->text('catatan_mcu')->nullable()->after('status_mcu');
        });
    }

    public function down(): void
    {
        Schema::table('mcu_invitees', function (Blueprint $table) {
            $table->dropColumn(['status_mcu', 'catatan_mcu']);
        });

        Schema::table('mcu_invitees', function (Blueprint $table) {
            $table->enum('result', ['lulus', 'tidak_lulus'])->nullable();
            $table->text('health_notes')->nullable();
        });
    }
};
