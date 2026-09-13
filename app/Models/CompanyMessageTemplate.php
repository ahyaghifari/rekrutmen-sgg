<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyMessageTemplate extends Model
{
    use HasUlids;

    protected $fillable = ['company_id', 'event', 'subject', 'body'];

    public const EVENT_SCREENING_REJECTED  = 'screening_rejected';
    public const EVENT_SCREENING_PASSED    = 'screening_passed';
    public const EVENT_QUIZ_SENT           = 'quiz_sent';
    public const EVENT_QUIZ_PASSED         = 'quiz_passed';
    public const EVENT_QUIZ_REJECTED       = 'quiz_rejected';
    public const EVENT_INTERVIEW_SCHEDULED = 'interview_scheduled';
    public const EVENT_INTERVIEW_PASSED    = 'interview_passed';
    public const EVENT_INTERVIEW_REJECTED  = 'interview_rejected';
    public const EVENT_MCU_SCHEDULED       = 'mcu_scheduled';
    public const EVENT_MCU_PASSED          = 'mcu_passed';
    public const EVENT_MCU_REJECTED        = 'mcu_rejected';
    public const EVENT_ONBOARDING_INVITE   = 'onboarding_invite';
    public const EVENT_OFFERING_LETTER_READY = 'offering_letter_ready';
    public const EVENT_OFFERING_SIGNED_RECEIVED   = 'offering_signed_received';
    public const EVENT_OFFERING_SIGNED_ACCEPTED   = 'offering_signed_accepted';
    public const EVENT_OFFERING_REVISION_REQUESTED = 'offering_revision_requested';
    public const EVENT_MANUAL_REJECTED     = 'manual_rejected';
    public const EVENT_OFFLINE_TEST_SCHEDULED = 'offline_test_scheduled';
    public const EVENT_OFFLINE_TEST_RESCHEDULED = 'offline_test_rescheduled';

    public static function forCompanyEvent(int $companyId, string $event): ?self
    {
        return static::where('company_id', $companyId)->where('event', $event)->first();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
