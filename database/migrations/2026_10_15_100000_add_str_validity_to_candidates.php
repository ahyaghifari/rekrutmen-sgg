<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Masa berlaku STR kandidat nakes: seumur hidup (tanpa tanggal) atau rentang tanggal. */
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->boolean('str_seumur_hidup')->default(false)->after('no_str');
            $table->date('str_berlaku_mulai')->nullable()->after('str_seumur_hidup');
            $table->date('str_berlaku_sampai')->nullable()->after('str_berlaku_mulai');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn(['str_seumur_hidup', 'str_berlaku_mulai', 'str_berlaku_sampai']);
        });
    }
};
