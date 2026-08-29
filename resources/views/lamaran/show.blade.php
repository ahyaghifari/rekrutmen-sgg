<x-app-layout>
<x-slot name="title">Detail Lamaran — {{ $application->jobOpening->title }}</x-slot>

@php
$statusMap = [
    'applied'     => ['label' => 'Lamaran Masuk',      'badge' => 'badge-applied',   'icon' => 'fa-paper-plane'],
    'screening'   => ['label' => 'Peninjauan Berkas',  'badge' => 'badge-screening', 'icon' => 'fa-magnifying-glass'],
    'shortlisted' => ['label' => 'Tes',         'badge' => 'badge-interview', 'icon' => 'fa-laptop'],
    'interview'   => ['label' => 'Wawancara',          'badge' => 'badge-interview', 'icon' => 'fa-comments'],
    'offering'    => ['label' => 'Offering Letter',    'badge' => 'badge-interview', 'icon' => 'fa-file-signature'],
    'mcu'         => ['label' => 'Medical Check Up',   'badge' => 'badge-interview', 'icon' => 'fa-stethoscope'],
    'onboarding'  => ['label' => 'TTD & Onboarding',   'badge' => 'badge-passed',    'icon' => 'fa-signature'],
    'passed'      => ['label' => 'Diterima',           'badge' => 'badge-passed',    'icon' => 'fa-circle-check'],
    'rejected'    => ['label' => 'Tidak Dilanjutkan',  'badge' => 'badge-rejected',  'icon' => 'fa-circle-xmark'],
];
$co          = $application->jobOpening->company;
$requiresMcu = $co?->requires_mcu ?? true;

$steps = [
    ['status' => 'applied',     'label' => 'Lamaran Masuk',     'sub' => 'Formulir terkirim',    'icon' => 'fa-paper-plane'],
    ['status' => 'screening',   'label' => 'Peninjauan Berkas', 'sub' => 'Tinjauan berkas awal', 'icon' => 'fa-magnifying-glass'],
    ['status' => 'shortlisted', 'label' => 'Tes',        'sub' => 'Kerjakan tes',         'icon' => 'fa-laptop'],
    ['status' => 'interview',   'label' => 'Wawancara',         'sub' => 'Jadwal dikirim',       'icon' => 'fa-comments'],
    ['status' => 'offering',    'label' => 'Offering Letter',   'sub' => 'Surat penawaran',      'icon' => 'fa-file-signature'],
];
if ($requiresMcu) {
    $steps[] = ['status' => 'mcu', 'label' => 'MCU', 'sub' => 'Medical check up', 'icon' => 'fa-stethoscope'];
}
$steps[] = ['status' => 'onboarding', 'label' => 'TTD & Onboarding', 'sub' => 'Kontrak & orientasi', 'icon' => 'fa-signature'];
$steps[] = ['status' => 'passed', 'label' => 'Keputusan', 'sub' => match ($application->status) {
    'passed'   => 'Selamat, diterima!',
    'rejected' => 'Tidak dilanjutkan',
    default    => 'Menunggu keputusan',
}, 'icon' => match ($application->status) {
    'passed'   => 'fa-circle-check',
    'rejected' => 'fa-circle-xmark',
    default    => 'fa-award',
}];

$isRejected  = $application->status === 'rejected';
// ponytail: kalau ditolak, berhenti di tahap terakhir yang benar-benar dijalani, bukan lompat ke node "Keputusan" di ujung.
$rejectedFromStatus = $isRejected
    ? $application->statusLogs()->where('to_status', 'rejected')->latest('changed_at')->first()?->from_status
    : null;
$currentStep = $isRejected
    ? (collect($steps)->search(fn ($s) => $s['status'] === $rejectedFromStatus) ?: 0)
    : (collect($steps)->search(fn ($s) => $s['status'] === $application->status) ?: 0);
$answers     = $application->answersMap();
$brand       = $co?->brand_color ?? '#042c6c';
$sectionColors = ['#042c6c','#0284c7','#b45309','#047C54','#B9308C','#7c3aed','#0f6e3d'];
@endphp

{{-- Back --}}
<div class="mb-5 flex items-center gap-3">
  <a href="{{ route('dashboard') }}"
     class="sg-auth-back w-[36px] h-[36px] shrink-0 rounded-[11px] border border-slate-200 grid place-items-center no-underline hover:border-slate-300 transition-colors duration-150">
    <i class="fa-solid fa-arrow-left text-sm text-slate-600"></i>
  </a>
  <div>
    <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.06em]">Detail Lamaran</div>
    <div class="text-[15px] font-extrabold text-slate-800 tracking-[-0.3px]">{{ $application->jobOpening->title }}</div>
  </div>
</div>

{{-- Position card --}}
<div class="relative overflow-hidden rounded-[20px] text-white px-7 py-6"
     style="background:linear-gradient(135deg,{{ $brand }} 0%,{{ $brand }}cc 100%);">
  <div class="absolute w-[220px] h-[220px] rounded-full -right-[60px] -top-[100px] pointer-events-none bg-white/[0.06]"></div>
  <div class="absolute w-[110px] h-[110px] rounded-[26px] rotate-[22deg] right-[150px] -bottom-[55px] pointer-events-none" style="background:rgba(185,48,140,0.18)"></div>
  <div class="relative z-[1] flex flex-wrap items-start justify-between gap-4">
    <div>
      <div class="text-[11.5px] font-bold tracking-[0.08em] uppercase text-white/70">Melamar untuk</div>
      <h1 class="mt-2 mb-1 text-2xl font-extrabold tracking-[-0.5px]">{{ $application->jobOpening->title }}</h1>
      <div class="text-[13.5px] text-white/80">{{ $co?->name ?? $application->jobOpening->company_name }}</div>
      <div class="mt-2 text-[12.5px] text-white/60">Dikirim {{ $application->created_at->translatedFormat('j F Y, H:i') }} WIB</div>
    </div>
    <span class="badge {{ $statusMap[$application->status]['badge'] ?? 'badge-applied' }} mt-1 text-[12px] px-[12px] py-[5px]">
      <i class="fa-solid {{ $statusMap[$application->status]['icon'] ?? 'fa-paper-plane' }}"></i>
      {{ $statusMap[$application->status]['label'] ?? $application->status }}
    </span>
  </div>
</div>

{{-- Status timeline --}}
<div class="sg-card mt-4 rounded-[18px] px-6 py-5">
  <div class="text-[12px] font-bold uppercase tracking-[0.06em] text-slate-500 mb-5">Tahap Seleksi</div>
  @php $lastIdx = count($steps) - 1; @endphp
  <div style="display:flex;gap:0;">
    @foreach($steps as $i => $step)
    @php
      $done    = $i < $currentStep;
      $current = $i === $currentStep;
      $future  = $i > $currentStep;
      $activeColor = ($isRejected && $current) ? '#dc2626' : $brand;
    @endphp
    <div style="flex:1;text-align:center;position:relative;padding:0 4px;">
      @if($i > 0)
      <div class="sg-tl-line {{ $done || $current ? '' : 'sg-tl-track-todo' }}"
           style="position:absolute;top:19px;left:0;right:50%;height:2px;{{ $done || $current ? 'background:'.$brand : '' }};"></div>
      @endif
      @if($i < $lastIdx)
      <div class="sg-tl-line {{ $done ? '' : 'sg-tl-track-todo' }}"
           style="position:absolute;top:19px;left:50%;right:0;height:2px;{{ $done ? 'background:'.$brand : '' }};"></div>
      @endif
      <div class="{{ $future ? 'sg-tl-node-todo' : '' }}"
           style="width:38px;height:38px;border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center;font-size:12px;position:relative;z-index:1;{{ !$future ? 'background:'.$activeColor.';color:#fff;' : '' }}">
        @if($done)
          <i class="fa-solid fa-check"></i>
        @elseif($current && $isRejected)
          <i class="fa-solid fa-xmark"></i>
        @else
          <i class="fa-solid {{ $step['icon'] }}"></i>
        @endif
      </div>
      <div class="{{ $future ? 'sg-tl-label-todo' : '' }}"
           style="margin-top:8px;font-size:11.5px;font-weight:700;line-height:1.3;{{ !$future ? 'color:'.$activeColor : '' }};">{{ $step['label'] }}</div>
      <div class="sg-tl-sub">{{ $step['sub'] }}</div>
    </div>
    @endforeach
  </div>
</div>

@if($application->status === 'interview')
@php $interviewSchedule = $application->interviewInvitee?->schedule; @endphp
<div class="sg-card mt-3 rounded-[18px] px-6 py-5" style="border-left: 4px solid {{ $brand }};">
  <div class="flex items-start gap-3">
    <i class="fa-solid fa-calendar-days mt-[3px] text-sm" style="color:{{ $brand }};"></i>
    <div class="text-[13.5px] text-slate-700">
      @if($interviewSchedule)
        <div class="font-extrabold text-slate-800 mb-1">Jadwal Wawancara</div>
        <div>{{ $interviewSchedule->interview_date?->translatedFormat('l, j F Y') }}
          @if($interviewSchedule->interview_time) &middot; {{ substr($interviewSchedule->interview_time, 0, 5) }} WIB @endif
        </div>
        @if($interviewSchedule->location)<div class="mt-1">Lokasi: {{ $interviewSchedule->location }}</div>@endif
        @if($interviewSchedule->notes)<div class="mt-1 text-slate-500">Catatan: {{ $interviewSchedule->notes }}</div>@endif
      @else
        <div class="font-extrabold text-slate-800 mb-1">Jadwal Wawancara</div>
        <div class="text-slate-500">Belum diinformasikan — tim rekrutmen akan menghubungimu untuk jadwal wawancara.</div>
      @endif
    </div>
  </div>
</div>
@endif

@if($application->status === 'mcu')
@php $mcuSchedule = $application->mcuInvitee?->schedule; @endphp
<div class="sg-card mt-3 rounded-[18px] px-6 py-5" style="border-left: 4px solid {{ $brand }};">
  <div class="flex items-start gap-3">
    <i class="fa-solid fa-calendar-days mt-[3px] text-sm" style="color:{{ $brand }};"></i>
    <div class="text-[13.5px] text-slate-700">
      @if($mcuSchedule)
        <div class="font-extrabold text-slate-800 mb-1">Jadwal Medical Check Up</div>
        <div>{{ $mcuSchedule->mcu_date?->translatedFormat('l, j F Y') }}
          @if($mcuSchedule->mcu_time) &middot; {{ substr($mcuSchedule->mcu_time, 0, 5) }} WIB @endif
        </div>
        @if($mcuSchedule->location)<div class="mt-1">Lokasi: {{ $mcuSchedule->location }}</div>@endif
        @if($mcuSchedule->notes)<div class="mt-1 text-slate-500">Catatan: {{ $mcuSchedule->notes }}</div>@endif
      @else
        <div class="font-extrabold text-slate-800 mb-1">Jadwal Medical Check Up</div>
        <div class="text-slate-500">Belum diinformasikan — tim rekrutmen akan menghubungimu untuk jadwal MCU.</div>
      @endif
    </div>
  </div>
</div>
@endif

{{-- Answer sections — driven by company formSections --}}
@if($co?->formSections->isNotEmpty())
  @foreach($co->formSections as $sectionIndex => $formSection)
  @php $secColor = $sectionColors[$sectionIndex % count($sectionColors)]; @endphp
  <div class="sg-card mt-3 rounded-[18px] py-[22px] px-6" style="border-top: 3px solid {{ $secColor }};">
    <div class="flex gap-3 items-center mb-4">
      <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12px] font-extrabold"
           style="color:{{ $secColor }};background:{{ $secColor }}1a;">{{ $sectionIndex + 1 }}</div>
      <h2 class="m-0 text-[16px] font-extrabold text-slate-800 tracking-[-0.3px]">{{ $formSection->title }}</h2>
    </div>
    <div class="sg-fields">
      @foreach($formSection->fields as $field)
      @php
        $val     = $answers[$field->name] ?? null;
        $isWide  = in_array($field->type, ['textarea']);
        $isEmpty = $val === null || $val === '';
      @endphp
      <div @if($isWide) class="col-span-full" @endif>
        <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">{{ $field->label }}</div>
        @if($field->type === 'file')
          @if(!$isEmpty)
          <a href="{{ route('lamaran.attachment', ['application' => $application->id, 'field' => $field->name]) }}" target="_blank"
             class="inline-flex items-center gap-2 px-4 py-[9px] rounded-[10px] border border-slate-200 text-[13px] font-semibold text-slate-700 no-underline hover:border-navy hover:text-navy transition-colors duration-150" style="background:rgba(0,0,0,0.02);">
            <i class="fa-solid fa-file-pdf text-red-500"></i>Lihat Dokumen
          </a>
          @else
          <span class="text-[13px] text-slate-400">—</span>
          @endif
        @elseif($field->type === 'date' && !$isEmpty)
          <div class="text-[14px] font-semibold text-slate-800">
            {{ \Carbon\Carbon::parse($val)->translatedFormat('j F Y') }}
          </div>
        @elseif($field->type === 'number' && !$isEmpty)
          <div class="text-[14px] font-semibold text-slate-800">
            @php
              $numLabel = strtolower($field->name);
              $isSalary = str_contains($numLabel, 'salary') || str_contains($numLabel, 'gaji');
            @endphp
            {{ $isSalary ? 'Rp ' . number_format($val, 0, ',', '.') : $val }}
          </div>
        @elseif($field->type === 'textarea' && !$isEmpty)
          <div class="text-[14px] font-semibold text-slate-800 whitespace-pre-line">{{ $val }}</div>
        @else
          <div class="text-[14px] font-semibold {{ $isEmpty ? 'text-slate-400' : 'text-slate-800' }}">
            {{ $isEmpty ? '—' : $val }}
          </div>
        @endif
      </div>
      @endforeach
    </div>
  </div>
  @endforeach
@else
  {{-- Fallback: tampilkan raw answers jika formSections tidak ada --}}
  <div class="sg-card mt-3 rounded-[18px] py-[22px] px-6">
    <h2 class="m-0 mb-4 text-[16px] font-extrabold text-slate-800">Data Lamaran</h2>
    <div class="sg-fields">
      @foreach($answers as $key => $val)
      <div>
        <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">{{ str_replace('_', ' ', $key) }}</div>
        <div class="text-[14px] font-semibold text-slate-800">{{ $val ?? '—' }}</div>
      </div>
      @endforeach
    </div>
  </div>
@endif

</x-app-layout>
