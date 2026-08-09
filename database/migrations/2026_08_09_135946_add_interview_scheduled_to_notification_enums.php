<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE company_message_templates MODIFY COLUMN event ENUM('screening_rejected','screening_passed','interview_scheduled') NOT NULL");
        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN event ENUM('screening_rejected','screening_passed','interview_scheduled') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE company_message_templates MODIFY COLUMN event ENUM('screening_rejected','screening_passed') NOT NULL");
        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN event ENUM('screening_rejected','screening_passed') NOT NULL");
    }
};
