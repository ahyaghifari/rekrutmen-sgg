<?php

namespace App\Filament\Widgets;

use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecruitmentStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalApplicants  = JobApplication::count();
        $activeOpenings   = JobOpening::active()->count();
        $thisMonth        = JobApplication::whereMonth('created_at', now()->month)
                               ->whereYear('created_at', now()->year)->count();
        $inProgress       = JobApplication::whereIn('status', ['screening', 'shortlisted', 'interview'])->count();
        $passed           = JobApplication::where('status', 'passed')->count();
        $newApplicants    = JobApplication::where('status', 'applied')->count();

        return [
            Stat::make('Total Pelamar', $totalApplicants)
                ->description('Seluruh lamaran masuk')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Lowongan Aktif', $activeOpenings)
                ->description('Posisi yang sedang dibuka')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make('Lamaran Bulan Ini', $thisMonth)
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),

            Stat::make('Menunggu Review', $newApplicants)
                ->description('Status: Lamaran Masuk')
                ->descriptionIcon('heroicon-m-inbox')
                ->color('gray'),

            Stat::make('Proses Seleksi', $inProgress)
                ->description('Screening + Wawancara')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('warning'),

            Stat::make('Diterima', $passed)
                ->description('Pelamar lolos seleksi')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
