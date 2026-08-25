<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOpeningStageLog extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = ['job_opening_id', 'from_status', 'to_status', 'count', 'done_by', 'done_at'];

    protected $casts = ['done_at' => 'datetime'];

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    public static function record(string $jobOpeningId, ?string $from, string $to, int $count): void
    {
        if ($count <= 0) return;

        static::create([
            'job_opening_id' => $jobOpeningId,
            'from_status'    => $from,
            'to_status'      => $to,
            'count'          => $count,
            'done_by'        => auth()->id(),
            'done_at'        => now(),
        ]);
    }
}
