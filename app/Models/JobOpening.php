<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class JobOpening extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'id',
        'company_id',
        'title',
        'company_name',
        'quota',
        'max_applicants',
        'applications_deadline',
        'hris_job_opening_id',
        'is_active',
        'description',
        'requirements',
        'requires_psychotest',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'quota'                 => 'integer',
        'max_applicants'        => 'integer',
        'applications_deadline' => 'date',
        'requires_psychotest'   => 'boolean',
    ];

    /**
     * Lacak siapa yang bikin/edit/hapus lowongan — otomatis, tanpa perlu
     * tiap Filament action mengisinya sendiri. Hapus tetap soft delete supaya
     * "siapa yang menghapus" masih bisa dilihat setelahnya.
     */
    protected static function booted(): void
    {
        static::creating(function (JobOpening $opening) {
            $opening->created_by = auth()->id();
            $opening->updated_by = auth()->id();
        });

        static::updating(function (JobOpening $opening) {
            $opening->updated_by = auth()->id();
        });

        static::deleting(function (JobOpening $opening) {
            DB::table('job_openings')->where('id', $opening->id)->update(['deleted_by' => auth()->id()]);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function stageLogs(): HasMany
    {
        return $this->hasMany(JobOpeningStageLog::class)->orderBy('done_at');
    }

    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'job_opening_quiz');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Scope lowongan yang bisa dilamar pelamar (aktif saja).
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Tahap terjauh yang sudah dicapai kandidat di lowongan ini, dihitung langsung dari data
     * (bukan kolom tersimpan) — supaya tidak pernah basi walau kandidat bergerak di banyak tahap sekaligus.
     */
    public function currentStage(): array
    {
        $counts = $this->applications()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        if ($counts->sum() === 0) {
            return ['label' => 'Belum Ada Pelamar', 'color' => 'gray'];
        }

        if (($counts['onboarding'] ?? 0) > 0) {
            return ['label' => 'TTD & Onboarding', 'color' => 'success'];
        }

        if (($counts['mcu'] ?? 0) > 0) {
            return ['label' => 'MCU', 'color' => 'warning'];
        }

        if (($counts['offering'] ?? 0) > 0) {
            return ['label' => 'Offering Letter', 'color' => 'warning'];
        }

        if (($counts['interview'] ?? 0) > 0) {
            return ['label' => 'Wawancara', 'color' => 'warning'];
        }

        if (($counts['shortlisted'] ?? 0) > 0) {
            if (! $this->quizzes()->exists()) {
                return ['label' => 'Tes: Kuis Belum Dipasang', 'color' => 'danger'];
            }

            $anyActivated = $this->applications()->where('status', 'shortlisted')->whereNotNull('quiz_sent_at')->exists();

            return $anyActivated
                ? ['label' => 'Tes: Berjalan', 'color' => 'info']
                : ['label' => 'Tes: Belum Diaktifkan', 'color' => 'warning'];
        }

        if (($counts['applied'] ?? 0) > 0 || ($counts['screening'] ?? 0) > 0) {
            return ['label' => 'Seleksi Berkas', 'color' => 'gray'];
        }

        if (($counts['passed'] ?? 0) > 0) {
            return ['label' => 'Selesai', 'color' => 'success'];
        }

        return ['label' => 'Tidak Ada Kandidat Aktif', 'color' => 'gray'];
    }
}
