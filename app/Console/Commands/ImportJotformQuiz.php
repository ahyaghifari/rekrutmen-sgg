<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportJotformQuiz extends Command
{
    protected $signature = 'quiz:import-jotform {file : path JSON export Jotform} {--assets= : folder gambar (default: assets/<nama-file> di sebelah JSON)} {--name= : nama kuis} {--dry-run : simulasi, tidak menyimpan apa pun}';
    protected $description = 'Import kuis dari export Jotform (soal radio/checkbox ber-calcValues, heading jadi subtest, gambar dari folder assets).';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) return $this->fail("File tidak ada: {$file}");

        $content = json_decode(file_get_contents($file), true)['content'] ?? null;
        if (! $content) return $this->fail('JSON tidak punya "content".');

        $base   = pathinfo($file, PATHINFO_FILENAME);
        $assets = $this->option('assets') ?: dirname($file) . '/assets/' . $base;
        $items  = collect($content)->sortBy(fn ($q) => (int) $q['order'])->values();

        $title = $items->firstWhere('type', 'control_head');
        $name  = $this->option('name') ?: ($title['text'] ?? $base);
        if (Quiz::where('name', $name)->exists()) return $this->fail("Kuis \"{$name}\" sudah ada. Pakai --name untuk nama lain.");

        $dry = $this->option('dry-run');
        DB::beginTransaction();
        try {
            $quiz = Quiz::create(['name' => $name, 'instructions' => $title['subHeader'] ?? null]);

            $section = null; $sectionSort = 0; $sort = 0; $image = null; $created = 0; $missing = 0;

            foreach ($items as $it) {
                switch ($it['type']) {
                    case 'control_head':
                        if ($it === $title) break;
                        $section = QuizSection::create([
                            'quiz_id' => $quiz->id, 'name' => $it['text'],
                            'instructions' => ($it['subHeader'] ?? '') ?: null, 'sort_order' => $sectionSort++,
                        ]);
                        break;

                    case 'control_image':
                        $image = $it['src'] ?? null;
                        break;

                    case 'control_radio':
                    case 'control_checkbox':
                        if (empty($it['calcValues']) || empty($it['options'])) break; // bukan soal ber-kunci

                        $labels  = array_map(fn ($o) => preg_replace('/^[A-Za-z]\.\s*/', '', $o), explode('|', $it['options']));
                        $vals    = explode('|', $it['calcValues']);
                        $correct = collect($labels)->filter(fn ($l, $i) => (float) ($vals[$i] ?? 0) > 0)->values();
                        if ($correct->isEmpty()) { $this->warn("Lewati \"{$it['text']}\": tidak ada kunci."); break; }

                        $multi = $it['type'] === 'control_checkbox';
                        $path  = null;
                        if ($image) {
                            $src = $assets . '/' . rawurldecode(basename($image));
                            if (! is_file($src)) {
                                $missing++; $this->warn("Gambar tidak ada: {$src}");
                            } elseif (! $dry) {
                                $path = 'quiz-questions/' . Str::ulid() . '.' . pathinfo($src, PATHINFO_EXTENSION);
                                Storage::disk('public')->put($path, file_get_contents($src));
                            }
                        }

                        QuizQuestion::create([
                            'quiz_id'         => $quiz->id,
                            'quiz_section_id' => $section?->id,
                            'question'        => $it['text'],
                            'image_path'      => $path,
                            'options'         => $labels,
                            'correct_option'  => $multi ? $correct->implode(',') : $correct->first(),
                            'max_select'      => $multi ? max(2, (int) ($it['maxSelection'] ?: $correct->count())) : null,
                            'sort_order'      => $sort++,
                        ]);
                        $image = null;
                        $created++;
                        break;
                }
            }

            $this->info(($dry ? '[DRY-RUN] ' : '') . "Kuis \"{$name}\" dibuat: {$sectionSort} subtest, {$created} soal, {$missing} gambar hilang. ID: {$quiz->id}");
            $dry ? DB::rollBack() : DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return self::SUCCESS;
    }
}
