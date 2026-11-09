<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Penentu keputusan reschedule adalah staf HRIS (ID ULID), bukan user rekrutmen (bigint).
return new class extends Migration
{
    public function up(): void
    {
        foreach (['interview_invitees', 'mcu_invitees'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->string('reschedule_decided_by', 26)->nullable()->change());
        }
    }

    public function down(): void
    {
        foreach (['interview_invitees', 'mcu_invitees'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->unsignedBigInteger('reschedule_decided_by')->nullable()->change());
        }
    }
};
