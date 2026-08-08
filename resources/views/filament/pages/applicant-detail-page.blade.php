<x-filament-panels::page>

@php
$application = $this->application;
$answers     = $application->answersMap();
$co          = $application->jobOpening->company;
$brand       = $co?->brand_color ?? '#042c6c';
$statusMap   = [
    'applied'   => ['label' => 'Lamaran Masuk',    'color' => 'gray'],
    'screening' => ['label' => 'Screening',          'color' => 'info'],
    'interview' => ['label' => 'Wawancara',          'color' => 'warning'],
    'passed'    => ['label' => 'Diterima',           'color' => 'success'],
    'rejected'  => ['label' => 'Tidak Dilanjutkan', 'color' => 'danger'],
];
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

{{-- Status changer --}}
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
    <div class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">Ubah Status Lamaran</div>
    <div class="flex flex-wrap gap-2">
        @foreach($statusMap as $key => $info)
        <button
            wire:click="changeStatus('{{ $key }}')"
            class="px-4 py-2 rounded-lg text-sm font-semibold border transition-all duration-150
                {{ $application->status === $key
                    ? 'bg-primary-600 text-white border-primary-600'
                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-primary-400' }}">
            {{ $info['label'] }}
        </button>
        @endforeach
    </div>
</div>

{{-- Answer sections --}}
@if($co?->formSections->isNotEmpty())
    @foreach($co->formSections as $idx => $section)
    @php $secColor = $sectionColors[$idx % count($sectionColors)]; @endphp
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
        {{-- Section header --}}
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 dark:border-white/10"
             style="border-left: 4px solid {{ $secColor }};">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-extrabold shrink-0"
                 style="color:{{ $secColor }};background:{{ $secColor }}1a;">{{ $idx + 1 }}</div>
            <h3 class="text-base font-extrabold text-gray-800 dark:text-gray-100 m-0">{{ $section->title }}</h3>
        </div>

        {{-- Fields grid --}}
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
            @foreach($section->fields as $field)
            @php
                $val     = $answers[$field->name] ?? null;
                $isEmpty = $val === null || $val === '';
                $isWide  = $field->type === 'textarea';
            @endphp
            <div @if($isWide) class="sm:col-span-2" @endif>
                <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">
                    {{ $field->label }}
                </div>

                @if($field->type === 'file')
                    @if(!$isEmpty)
                    <a href="{{ asset('storage/' . $val) }}" target="_blank"
                       class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-300 no-underline hover:border-primary-400 transition-colors">
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
                    @php
                        $isSalary = str_contains(strtolower($field->name), 'salary') || str_contains(strtolower($field->name), 'gaji');
                    @endphp
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
