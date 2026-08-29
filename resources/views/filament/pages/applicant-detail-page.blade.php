<x-filament-panels::page>

@php
$application = $this->application;
$answers     = $application->answersMap();
$co          = $application->jobOpening->company;
$brand       = $co?->brand_color ?? '#042c6c';
$statusMap   = [
    'applied'     => ['label' => 'Lamaran Masuk',        'color' => 'gray'],
    'screening'   => ['label' => 'Peninjauan Berkas',    'color' => 'info'],
    'shortlisted' => ['label' => 'Lolos Seleksi Berkas', 'color' => 'warning'],
    'interview'   => ['label' => 'Wawancara',            'color' => 'warning'],
    'quiz_lulus'  => ['label' => 'Lulus Tes',            'color' => 'success'],
    'offering'    => ['label' => 'Offering Letter',      'color' => 'warning'],
    'mcu'         => ['label' => 'Medical Check Up',     'color' => 'warning'],
    'onboarding'  => ['label' => 'TTD & Onboarding',     'color' => 'success'],
    'passed'      => ['label' => 'Diterima',             'color' => 'success'],
    'rejected'    => ['label' => 'Tidak Dilanjutkan',    'color' => 'danger'],
];
$s = $statusMap[$application->status] ?? ['label' => $application->status, 'color' => 'gray'];
@endphp

{{-- Back --}}
<div class="-mt-2 mb-2">
    <a href="{{ route('filament.admin.pages.applicants-page') }}?job_opening_id={{ $application->job_opening_id }}"
       class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100 no-underline transition-colors">
        <x-heroicon-m-arrow-left class="w-4 h-4" />
        Kembali ke Daftar Pelamar
    </a>
</div>

{{-- Header --}}
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
        <x-filament::badge :color="$s['color']" size="lg">{{ $s['label'] }}</x-filament::badge>
    </div>
</div>

{{-- Riwayat Status --}}
<x-filament::section heading="Riwayat Status" icon="heroicon-m-clock" collapsible collapsed>
    @php $logs = $application->statusLogs; @endphp
    @if($logs->isEmpty())
        <p class="text-sm text-gray-400 dark:text-gray-500">Belum ada riwayat.</p>
    @else
        <div class="flex flex-col gap-3">
            @foreach($logs as $log)
            <div class="flex items-start gap-3">
                <x-filament::badge :color="$statusMap[$log->to_status]['color'] ?? 'gray'" size="sm">
                    {{ $statusMap[$log->to_status]['label'] ?? $log->to_status }}
                </x-filament::badge>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $log->changed_at->translatedFormat('j F Y, H:i') }} WIB
                    @if($log->changedBy) · oleh <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $log->changedBy->name }}</span> @endif
                    @if($log->from_status)
                        <span class="ml-1 text-xs text-gray-400">
                            (dari {{ $statusMap[$log->from_status]['label'] ?? $log->from_status }})
                        </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    @endif
</x-filament::section>

@php
    $isFinalStage = in_array($application->status, ['onboarding', 'passed']);
@endphp

{{-- Data Diri Kandidat — ditonjolkan begitu masuk TTD & Onboarding / selesai --}}
@if($isFinalStage && $application->candidate)
    @include('filament.pages.partials.candidate-profile-view', ['candidate' => $application->candidate])
@endif

{{-- Jawaban Form per Seksi — collapse begitu masuk TTD & Onboarding / selesai, data diri kandidat jadi sorotan utama --}}
@if($co?->formSections->isNotEmpty())
    @foreach($co->formSections as $section)
    <x-filament::section :heading="$section->title" icon="heroicon-m-document-text" :collapsible="$isFinalStage" :collapsed="$isFinalStage">
        <dl class="divide-y divide-gray-100 dark:divide-white/10">
            @foreach($section->fields as $field)
            @php
                $val     = $answers[$field->name] ?? null;
                $isEmpty = $val === null || $val === '';
            @endphp
            <div class="py-3 first:pt-0 last:pb-0 flex flex-col gap-1">
                <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $field->label }}</dt>
                <dd>
                    @if($field->type === 'file')
                        @if(!$isEmpty)
                        <x-filament::button
                            tag="a"
                            href="{{ route('lamaran.attachment', ['application' => $application->id, 'field' => $field->name]) }}"
                            target="_blank"
                            icon="heroicon-m-document"
                            color="gray"
                            outlined
                            size="sm">
                            Lihat Dokumen
                        </x-filament::button>
                        @else
                        <span class="text-sm text-gray-400">—</span>
                        @endif

                    @elseif($field->type === 'date' && !$isEmpty)
                        <span class="text-sm text-gray-950 dark:text-white">
                            {{ \Carbon\Carbon::parse($val)->translatedFormat('j F Y') }}
                        </span>

                    @elseif($field->type === 'number' && !$isEmpty)
                        @php $isSalary = str_contains($field->name, 'salary') || str_contains($field->name, 'gaji'); @endphp
                        <span class="text-sm text-gray-950 dark:text-white">
                            {{ $isSalary ? 'Rp ' . number_format($val, 0, ',', '.') : $val }}
                        </span>

                    @elseif($field->type === 'textarea' && !$isEmpty)
                        <span class="text-sm text-gray-950 dark:text-white whitespace-pre-line leading-relaxed">{{ $val }}</span>

                    @else
                        <span class="text-sm {{ $isEmpty ? 'text-gray-400' : 'text-gray-950 dark:text-white' }}">
                            {{ $isEmpty ? '—' : $val }}
                        </span>
                    @endif
                </dd>
            </div>
            @endforeach
        </dl>
    </x-filament::section>
    @endforeach
@endif

{{-- Tidak Dilanjutkan --}}
@if(! in_array($application->status, ['rejected', 'passed']))
<x-filament::section heading="Tidak Dilanjutkan" icon="heroicon-m-x-circle" collapsible collapsed>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
        Tandai pelamar ini tidak dilanjutkan di luar alur normal — misalnya kandidat mengundurkan diri di tengah proses, bukan karena gagal di salah satu tahap.
    </p>
    <x-filament::button
        wire:click="changeStatus('rejected')"
        wire:confirm="Yakin ingin menandai pelamar ini tidak dilanjutkan?"
        wire:loading.attr="disabled"
        color="danger"
        size="sm">
        Tidak Dilanjutkan
    </x-filament::button>
</x-filament::section>
@endif

</x-filament-panels::page>
