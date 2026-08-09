<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreeningDecision extends Model
{
    use HasUlids;

    protected $fillable = ['job_application_id', 'decision'];

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /** Simpan draft keputusan untuk satu job opening — hapus lama, tulis baru */
    public static function saveDraftForOpening(string $jobOpeningId, array $passedIds, array $allScreeningIds): void
    {
        // Hapus draft lama untuk opening ini
        static::whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $jobOpeningId))->delete();

        foreach ($allScreeningIds as $appId) {
            static::create([
                'job_application_id' => $appId,
                'decision'           => in_array($appId, $passedIds) ? 'passed' : 'rejected',
            ]);
        }
    }

    /** Terapkan semua draft untuk satu job opening */
    public static function applyForOpening(string $jobOpeningId): array
    {
        $decisions = static::with(['jobApplication.answers', 'jobApplication.jobOpening'])
            ->whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $jobOpeningId))
            ->get();

        $passed = 0;
        $rejected = 0;

        foreach ($decisions as $d) {
            $app = $d->jobApplication;
            if ($d->decision === 'passed') {
                $app->update(['status' => 'shortlisted']);
                ApplicationNotification::createDraftFor($app, CompanyMessageTemplate::EVENT_SCREENING_PASSED);
                $passed++;
            } else {
                $app->update(['status' => 'rejected']);
                ApplicationNotification::createDraftFor($app, CompanyMessageTemplate::EVENT_SCREENING_REJECTED);
                $rejected++;
            }
            $d->delete();
        }

        return [$passed, $rejected];
    }
}
