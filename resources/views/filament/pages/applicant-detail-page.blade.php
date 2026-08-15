<x-filament-panels::page>

@php
$application = $this->application;
$answers     = $application->answersMap();
$co          = $application->jobOpening->company;
$brand       = $co?->brand_color ?? '#042c6c';
$statusMap   = [
    'applied'     => ['label' => 'Lamaran Masuk',        'color' => 'gray',    'icon' => 'heroicon-m-paper-airplane'],
    'screening'   => ['label' => 'Peninjauan Berkas',    'color' => 'info',    'icon' => 'heroicon-m-magnifying-glass'],
    'shortlisted' => ['label' => 'Lolos Seleksi Berkas', 'color' => 'warning', 'icon' => 'heroicon-m-clipboard-document-check'],
    'interview'   => ['label' => 'Wawancara',            'color' => 'warning', 'icon' => 'heroicon-m-chat-bubble-left-right'],
    'mcu'         => ['label' => 'Medical Check Up',     'color' => 'warning', 'icon' => 'heroicon-m-heart'],
    'onboarding'  => ['label' => 'TTD & Onboarding',     'color' => 'success', 'icon' => 'heroicon-m-pencil-square'],
    'passed'      => ['label' => 'Diterima',             'color' => 'success', 'icon' => 'heroicon-m-check-badge'],
    'rejected'    => ['label' => 'Tidak Dilanjutkan',    'color' => 'danger',  'icon' => 'heroicon-m-x-circle'],
];
$steps = [
    ['label' => 'Lamaran Masuk',     'sub' => 'Formulir terkirim',         'icon' => 'fa-paper-plane',      'key' => 'applied'],
    ['label' => 'Peninjauan Berkas', 'sub' => 'Tinjauan berkas awal',      'icon' => 'fa-magnifying-glass', 'key' => 'screening'],
    ['label' => 'Seleksi Berkas',    'sub' => 'Keputusan lanjut/terhenti', 'icon' => 'fa-clipboard-check',  'key' => 'shortlisted'],
    ['label' => 'Wawancara',         'sub' => 'Jadwal dikirim',            'icon' => 'fa-comments',         'key' => 'interview'],
    ['label' => 'Keputusan',         'sub' => match($application->status) {
        'passed'   => 'Selamat, diterima!',
        'rejected' => 'Tidak dilanjutkan',
        default    => 'Menunggu keputusan',
    }, 'icon' => match($application->status) {
        'passed'   => 'fa-circle-check',
        'rejected' => 'fa-circle-xmark',
        default    => 'fa-award',
    }, 'key' => 'decision'],
];
$currentStep = match($application->status) {
    'applied'            => 0,
    'screening'          => 1,
    'shortlisted'        => 2,
    'interview'          => 3,
    'passed', 'rejected' => 4,
    default              => 0,
};
$isRejected    = $application->status === 'rejected';
$sectionColors = ['#042c6c','#0284c7','#b45309','#047C54','#B9308C','#7c3aed','#0f6e3d'];
$s = $statusMap[$application->status] ?? ['label' => $application->status, 'color' => 'gray'];
@endphp

{{-- Back --}}
<div class="flex items-center gap-3 -mt-2 mb-2">
    <a href="{{ route('filament.admin.pages.applicants-page') }}?job_opening_id={{ $application->job_opening_id }}"
       class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100 no-underline transition-colors">
        <x-heroicon-m-arrow-left class="w-4 h-4" />
        Kembali ke Daftar Pelamar
    </a>
</div>

{{-- Header card --}}
<div class="relative overflow-hidden rounded-xl text-white px-6 py-5"
     style="background: linear-gradient(135deg, {{ $brand }} 0%, {{ $brand }}bb 100%);">
    <div class="absolute w-48 h-48 rounded-full -right-10 -top-20 bg-white/[0.06] pointer-events-none"></div>
    <div class="absolute w-24 h-24 rounded-2xl rotate-12 right-36 -bottom-10 bg-white/[0.05] pointer-events-none"></div>
    <div class="relative z-[1] flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="text-xs font-bold uppercase tracking-widest text-white/60 mb-1">Melamar untuk</div>
            <div class="text-xl font-extrabold tracking-tight">{{ $application->jobOpening->title }}</div>
            <div class="text-sm text-white/75 mt-1">{{ $co?->name ?? $application->jobOpening->company_name }}</div>
            <div class="text-xs text-white/50 mt-2">Dikirim {{ $application->created_at->translatedFormat('j F Y, H:i') }} WIB</div>
        </div>
        <x-filament::badge :color="$s['color']" size="lg">
            {{ $s['label'] }}
        </x-filament::badge>
    </div>
</div>

{{-- Timeline --}}
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 px-6 py-5">
    <div class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-5">Tahap Seleksi</div>
    @php $lastStepIdx = count($steps) - 1; @endphp
    <div class="flex">
        @foreach($steps as $i => $step)
        @php
            $done    = $i < $currentStep;
            $current = $i === $currentStep;
            $future  = $i > $currentStep;
            $bg      = $done ? $brand : ($current ? ($isRejected && $i === $lastStepIdx ? '#dc2626' : $brand) : 'transparent');
            $border  = $future ? '2px solid #d1d5db' : 'none';
            $iconClr = ($done || $current) ? '#fff' : '#9ca3af';
            $lblClr  = $future ? '#9ca3af' : $brand;
            $lineL   = ($done || $current) ? $brand : '#e5e7eb';
            $lineR   = $done ? $brand : '#e5e7eb';
        @endphp
        <div style="flex:1;text-align:center;position:relative;padding:0 4px;">
            @if($i > 0)
            <div style="position:absolute;top:16px;left:0;right:50%;height:2px;background:{{ $lineL }};"></div>
            @endif
            @if($i < $lastStepIdx)
            <div style="position:absolute;top:16px;left:50%;right:0;height:2px;background:{{ $lineR }};"></div>
            @endif
            <div style="width:32px;height:32px;border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center;font-size:11px;position:relative;z-index:1;background:{{ $bg }};color:{{ $iconClr }};border:{{ $border }};">
                @if($done)
                    <i class="fa-solid fa-check"></i>
                @else
                    <i class="fa-solid {{ $step['icon'] }}"></i>
                @endif
            </div>
            <div style="margin-top:7px;font-size:11px;font-weight:700;color:{{ $lblClr }};">{{ $step['label'] }}</div>
            <div style="margin-top:2px;font-size:10px;color:#9ca3af;">{{ $step['sub'] }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- Status changer --}}
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 px-6 py-5">
    <div class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-4">Ubah Status Lamaran</div>
    <div class="flex flex-wrap gap-2">
        @foreach($statusMap as $key => $info)
        @php
            $isActive = $application->status === $key;
            $colors = [
                'gray'    => ['active' => 'bg-gray-600 text-white border-gray-600',    'inactive' => 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-gray-400'],
                'info'    => ['active' => 'bg-blue-600 text-white border-blue-600',    'inactive' => 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-blue-400'],
                'warning' => ['active' => 'bg-amber-500 text-white border-amber-500',  'inactive' => 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-amber-400'],
                'success' => ['active' => 'bg-emerald-600 text-white border-emerald-600','inactive'=> 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-emerald-400'],
                'danger'  => ['active' => 'bg-red-600 text-white border-red-600',      'inactive' => 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-red-400'],
            ];
            $cls = $isActive ? $colors[$info['color']]['active'] : $colors[$info['color']]['inactive'];
        @endphp
        <button
            wire:click="changeStatus('{{ $key }}')"
            wire:loading.attr="disabled"
            class="px-4 py-2 rounded-lg text-sm font-semibold border transition-all duration-150 cursor-pointer {{ $cls }}">
            {{ $info['label'] }}
        </button>
        @endforeach
    </div>
</div>

{{-- Riwayat status --}}
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 px-6 py-5">
    <div class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-4">Riwayat Status</div>
    @php $logs = $application->statusLogs; @endphp
    @if($logs->isEmpty())
    <div class="text-sm text-gray-400">Belum ada riwayat.</div>
    @else
    <div class="flex flex-col gap-3">
        @foreach($logs as $log)
        <div class="flex items-start gap-3">
            <div class="w-2 h-2 rounded-full mt-1.5 shrink-0" style="background:{{ $statusMap[$log->to_status]['color'] ?? 'gray' }}"></div>
            <div class="flex-1 min-w-0">
                <div class="text-sm text-gray-700 dark:text-gray-200">
                    @if($log->from_status)
                        <span class="font-semibold">{{ $statusMap[$log->from_status]['label'] ?? $log->from_status }}</span>
                        →
                    @endif
                    <span class="font-semibold">{{ $statusMap[$log->to_status]['label'] ?? $log->to_status }}</span>
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                    {{ $log->changed_at->translatedFormat('j F Y, H:i') }} WIB
                    @if($log->changedBy)
                        · oleh {{ $log->changedBy->name }}
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Answer sections --}}
@if($co?->formSections->isNotEmpty())
    @foreach($co->formSections as $idx => $section)
    @php $secColor = $sectionColors[$idx % count($sectionColors)]; @endphp
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 dark:border-white/10"
             style="border-left: 4px solid {{ $secColor }};">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-extrabold shrink-0"
                 style="color:{{ $secColor }};background:{{ $secColor }}1a;">{{ $idx + 1 }}</div>
            <h3 class="text-sm font-extrabold text-gray-800 dark:text-gray-100 m-0">{{ $section->title }}</h3>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
            @foreach($section->fields as $field)
            @php
                $val     = $answers[$field->name] ?? null;
                $isEmpty = $val === null || $val === '';
                $isWide  = $field->type === 'textarea';
            @endphp
            <div @if($isWide) class="sm:col-span-2" @endif>
                <div class="text-[10.5px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">
                    {{ $field->label }}
                </div>

                @if($field->type === 'file')
                    @if(!$isEmpty)
                    <a href="{{ route('lamaran.attachment', ['application' => $application->id, 'field' => $field->name]) }}" target="_blank"
                       class="inline-flex items-center gap-2 px-3 py-[7px] rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-300 no-underline hover:border-blue-400 transition-colors">
                        <x-heroicon-m-document class="w-4 h-4 text-red-500" />
                        Lihat Dokumen
                    </a>
                    @else
                    <span class="text-sm text-gray-400">—</span>
                    @endif

                @elseif($field->type === 'date' && !$isEmpty)
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        {{ \Carbon\Carbon::parse($val)->translatedFormat('j F Y') }}
                    </div>

                @elseif($field->type === 'number' && !$isEmpty)
                    @php $isSalary = str_contains(strtolower($field->name), 'salary') || str_contains(strtolower($field->name), 'gaji'); @endphp
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        {{ $isSalary ? 'Rp ' . number_format($val, 0, ',', '.') : $val }}
                    </div>

                @elseif($field->type === 'textarea' && !$isEmpty)
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 whitespace-pre-line leading-relaxed">{{ $val }}</div>

                @else
                    <div class="text-sm font-semibold {{ $isEmpty ? 'text-gray-400' : 'text-gray-800 dark:text-gray-100' }}">
                        {{ $isEmpty ? '—' : $val }}
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
@endif

</x-filament-panels::page>
