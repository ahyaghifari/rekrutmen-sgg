<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfflineTestSchedule extends Model
{
    use HasUlids;

    protected $fillable = ['job_opening_id', 'offline_test_id', 'test_date', 'test_time', 'location', 'notes', 'status'];

    protected $casts = ['test_date' => 'date'];

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function offlineTest(): BelongsTo
    {
        return $this->belongsTo(OfflineTest::class);
    }

    public function invitees(): HasMany
    {
        return $this->hasMany(OfflineTestInvitee::class);
    }
}
