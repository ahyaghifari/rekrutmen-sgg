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

    private function dropAllFksReferencingJobApplications(): void
    {
        $fks = DB::select(
            "SELECT TABLE_NAME, CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND REFERENCED_TABLE_NAME = 'job_applications'
               AND REFERENCED_COLUMN_NAME = 'id'"
        );

        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE `{$fk->TABLE_NAME}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }
    }

    public function up(): void
    {
        // DROP semua FK dulu sebelum hapus data, agar DELETE bebas constraint
        $this->dropAllFksReferencingJobApplications();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('DELETE FROM job_application_answers');
        DB::statement('DELETE FROM offering_letters');
        DB::statement('DELETE FROM candidates');
        DB::statement('DELETE FROM job_applications');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 1. job_applications.id: bigint AUTO_INCREMENT → char(26)
        $idType = DB::select('SHOW COLUMNS FROM job_applications WHERE Field="id"')[0]->Type ?? '';
        if ($idType !== 'char(26)') {
            // FKs sudah di-drop di atas, langsung lanjut

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
        } else {
            $ansType = DB::select('SHOW COLUMNS FROM job_application_answers WHERE Field="job_application_id"')[0]->Type ?? '';
            if ($ansType !== 'char(26)') {
                DB::statement('ALTER TABLE job_application_answers MODIFY COLUMN job_application_id char(26) NOT NULL');
            }
        }
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
        } else {
            $olType = DB::select('SHOW COLUMNS FROM offering_letters WHERE Field="job_application_id"')[0]->Type ?? '';
            if ($olType !== 'char(26)') {
                DB::statement('ALTER TABLE offering_letters MODIFY COLUMN job_application_id char(26) NOT NULL');
            }
        }
        if (! $this->hasFk('offering_letters', 'offering_letters_job_application_id_foreign')) {
            DB::statement('ALTER TABLE offering_letters ADD CONSTRAINT offering_letters_job_application_id_foreign FOREIGN KEY (job_application_id) REFERENCES job_applications (id) ON DELETE CASCADE');
        }
    }

    public function down(): void
    {
        // Data sudah dihapus saat up() — tidak di-reverse
    }
};
