<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorApplication extends Model
{
    protected $fillable = ['user_id', 'jenis', 'company_id', 'unit_minat', 'status', 'data', 'files'];

    protected $casts = ['data' => 'array', 'files' => 'array'];

    public const STATUS = ['baru' => 'Lamaran diterima'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? ucfirst($this->status);
    }
}
