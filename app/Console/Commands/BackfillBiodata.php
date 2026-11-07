<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\JobApplication;
use Illuminate\Console\Command;

class BackfillBiodata extends Command
{
    protected $signature = 'biodata:backfill {--dry-run : hanya hitung, tidak menulis}';
    protected $description = 'Buat Candidate untuk lamaran yang biodatanya sudah terbuka (jadwal wawancara terbit / offering / mcu) dan isi kolom kosong dari data lamaran/offering. Aman diulang.';

    public function handle(): int
    {
        $created = 0;
        $open = 0;

        // Dari yang lama ke baru: lamaran terbaru yang menang menentukan job_application_id.
        JobApplication::with('user')->orderBy('created_at')->chunkById(200, function ($apps) use (&$created, &$open) {
            foreach ($apps as $app) {
                if (! $app->user || ! $app->biodataOpen()) continue;

                $open++;
                if ($this->option('dry-run')) {
                    Candidate::where('user_id', $app->user_id)->exists() || $created++;
                    continue;
                }

                $existed = Candidate::where('user_id', $app->user_id)->exists();
                Candidate::ensureFor($app);
                $existed || $created++;
            }
        }, 'id');

        $this->info(($this->option('dry-run') ? '[DRY-RUN] ' : '') . "{$open} lamaran dengan biodata terbuka, {$created} Candidate baru.");

        return self::SUCCESS;
    }
}
