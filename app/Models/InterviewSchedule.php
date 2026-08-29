<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterviewSchedule extends Model
{
    use HasUlids;

    protected $fillable = ['job_opening_id', 'interview_date', 'interview_time', 'location', 'notes', 'status'];

    protected $casts = ['interview_date' => 'date'];

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function invitees(): HasMany
    {
        return $this->hasMany(InterviewInvitee::class);
    }

    public static function draftForOpening(string $jobOpeningId): ?self
    {
        return static::where('job_opening_id', $jobOpeningId)->where('status', 'draft')->latest()->first();
    }

    public static function saveDraft(string $jobOpeningId, array $data, array $invitedIds): self
    {
        // Hapus draft lama
        static::where('job_opening_id', $jobOpeningId)->where('status', 'draft')->delete();

        $schedule = static::create([
            'job_opening_id' => $jobOpeningId,
            'interview_date' => $data['interview_date'],
            'interview_time' => $data['interview_time'] ?? null,
            'location'       => $data['location'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'status'         => 'draft',
        ]);

        foreach ($invitedIds as $appId) {
            InterviewInvitee::create([
                'interview_schedule_id' => $schedule->id,
                'job_application_id'    => $appId,
            ]);
        }

        return $schedule;
    }

    public function apply(): array
    {
        $invitees = $this->invitees()->with(['jobApplication.answers', 'jobApplication.jobOpening'])->get();

        foreach ($invitees as $inv) {
            $inv->jobApplication->update(['status' => 'interview']);
            ApplicationNotification::createAndSend($inv->jobApplication, CompanyMessageTemplate::EVENT_INTERVIEW_SCHEDULED, [
                'interview_date' => $this->interview_date?->translatedFormat('d F Y'),
                'interview_time' => $this->interview_time ? substr($this->interview_time, 0, 5) : null,
                'location'       => $this->location,
                'notes'          => $this->notes,
            ]);
        }

        $count = $invitees->count();
        $this->update(['status' => 'published']);

        return [$count];
    }
}
