<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE job_applications MODIFY COLUMN status ENUM('applied','screening','shortlisted','interview','passed','rejected') NOT NULL DEFAULT 'applied'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE job_applications MODIFY COLUMN status ENUM('applied','screening','interview','passed','rejected') NOT NULL DEFAULT 'applied'");
    }
};
