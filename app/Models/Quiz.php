<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasUlids;

    protected $fillable = ['name', 'duration_minutes', 'instructions'];

    protected $casts = ['duration_minutes' => 'integer'];

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order');
    }

    public function jobOpenings(): HasMany
    {
        return $this->hasMany(JobOpening::class);
    }
}
