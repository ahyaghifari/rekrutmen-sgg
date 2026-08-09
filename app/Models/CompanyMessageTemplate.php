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
    public const EVENT_INTERVIEW_SCHEDULED = 'interview_scheduled';

    public static function forCompanyEvent(int $companyId, string $event): ?self
    {
        return static::where('company_id', $companyId)->where('event', $event)->first();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
