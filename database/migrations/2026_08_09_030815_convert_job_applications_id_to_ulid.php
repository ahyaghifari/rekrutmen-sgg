<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasFk(string $table, string $fkName): bool
    {
        return DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?
             AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$table, $fkName]
        ) !== [];
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return DB::select(
            "SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$table, $indexName]
        ) !== [];
    }

    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('job_application_answers')->truncate();
        DB::table('offering_letters')->truncate();
        DB::table('candidates')->truncate();
        DB::table('job_applications')->truncate();

        // 1. job_applications.id: bigint AUTO_INCREMENT → char(26)
        $idType = DB::select('SHOW COLUMNS FROM job_applications WHERE Field="id"')[0]->Type ?? '';
        if ($idType !== 'char(26)') {
            if ($this->hasFk('candidates', 'candidates_job_application_id_foreign')) {
                DB::statement('ALTER TABLE candidates DROP FOREIGN KEY candidates_job_application_id_foreign');
            }
            DB::statement('ALTER TABLE job_applications MODIFY COLUMN id bigint(20) UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE job_applications DROP PRIMARY KEY');
            DB::statement('ALTER TABLE job_applications MODIFY COLUMN id char(26) NOT NULL');
            DB::statement('ALTER TABLE job_applications ADD PRIMARY KEY (id)');
        }

        // 2. candidates.job_application_id: bigint → char(26)
        $candType = DB::select('SHOW COLUMNS FROM candidates WHERE Field="job_application_id"')[0]->Type ?? '';
        if ($candType !== 'char(26)') {
            if ($this->hasFk('candidates', 'candidates_job_application_id_foreign')) {
                DB::statement('ALTER TABLE candidates DROP FOREIGN KEY candidates_job_application_id_foreign');
            }
            DB::statement('ALTER TABLE candidates MODIFY COLUMN job_application_id char(26) NULL');
        }
        if (! $this->hasFk('candidates', 'candidates_job_application_id_foreign')) {
            DB::statement('ALTER TABLE candidates ADD CONSTRAINT candidates_job_application_id_foreign FOREIGN KEY (job_application_id) REFERENCES job_applications (id) ON DELETE SET NULL');
        }

        // 3. job_application_answers.job_application_id
        if (! Schema::hasColumn('job_application_answers', 'job_application_id')) {
            DB::statement('ALTER TABLE job_application_answers ADD COLUMN job_application_id char(26) NOT NULL AFTER id');
        }
        // Fix compound unique — drop old (wrong) index first, then add correct compound
        if ($this->hasIndex('job_application_answers', 'job_application_answers_job_application_id_field_key_unique')) {
            DB::statement('ALTER TABLE job_application_answers DROP INDEX job_application_answers_job_application_id_field_key_unique');
        }
        DB::statement('ALTER TABLE job_application_answers ADD CONSTRAINT job_application_answers_job_application_id_field_key_unique UNIQUE (job_application_id, field_key)');
        if (! $this->hasFk('job_application_answers', 'job_application_answers_job_application_id_foreign')) {
            DB::statement('ALTER TABLE job_application_answers ADD CONSTRAINT job_application_answers_job_application_id_foreign FOREIGN KEY (job_application_id) REFERENCES job_applications (id) ON DELETE CASCADE');
        }

        // 4. offering_letters.job_application_id
        if (! Schema::hasColumn('offering_letters', 'job_application_id')) {
            DB::statement('ALTER TABLE offering_letters ADD COLUMN job_application_id char(26) NOT NULL AFTER id');
        }
        if (! $this->hasFk('offering_letters', 'offering_letters_job_application_id_foreign')) {
            DB::statement('ALTER TABLE offering_letters ADD CONSTRAINT offering_letters_job_application_id_foreign FOREIGN KEY (job_application_id) REFERENCES job_applications (id) ON DELETE CASCADE');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Data sudah dihapus saat up() — tidak di-reverse
    }
};
