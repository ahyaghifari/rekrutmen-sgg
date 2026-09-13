<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfflineTest extends Model
{
    use HasUlids;

    protected $fillable = ['name', 'description'];

    public function jobOpenings(): BelongsToMany
    {
        return $this->belongsToMany(JobOpening::class, 'job_opening_offline_test');
    }

    public function results(): HasMany
    {
        return $this->hasMany(OfflineTestResult::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(OfflineTestSchedule::class);
    }
}
