<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_EVENTS = [
        'screening_rejected', 'screening_passed', 'interview_scheduled', 'quiz_sent',
        'quiz_passed', 'quiz_rejected', 'interview_rejected', 'mcu_scheduled', 'mcu_rejected',
        'onboarding_invite', 'offering_letter_ready', 'mcu_passed',
    ];

    private const NEW_EVENTS = [...self::OLD_EVENTS, 'manual_rejected'];

    public function up(): void
    {
        $list = "'" . implode("','", self::NEW_EVENTS) . "'";
        DB::statement("ALTER TABLE company_message_templates MODIFY COLUMN event ENUM({$list}) NOT NULL");
        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN event ENUM({$list}) NOT NULL");
    }

    public function down(): void
    {
        $list = "'" . implode("','", self::OLD_EVENTS) . "'";
        DB::statement("ALTER TABLE company_message_templates MODIFY COLUMN event ENUM({$list}) NOT NULL");
        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN event ENUM({$list}) NOT NULL");
    }
};
