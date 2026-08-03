<x-app-layout>
<x-slot name="title">Dashboard — Syifa Global Group</x-slot>

@php
$statusMap = [
    'applied'   => ['label' => 'Lamaran Masuk',    'badge' => 'badge-applied',   'icon' => 'fa-paper-plane'],
    'screening' => ['label' => 'Screening',          'badge' => 'badge-screening', 'icon' => 'fa-magnifying-glass'],
    'interview' => ['label' => 'Wawancara',          'badge' => 'badge-interview', 'icon' => 'fa-comments'],
    'passed'    => ['label' => 'Diterima',           'badge' => 'badge-passed',    'icon' => 'fa-circle-check'],
    'rejected'  => ['label' => 'Tidak Dilanjutkan', 'badge' => 'badge-rejected',  'icon' => 'fa-circle-xmark'],
];
$activeApp = $applications->first(fn($a) => in_array($a->status, ['applied','screening','interview']));
$pastApps  = $applications->filter(fn($a) => in_array($a->status, ['passed','rejected']));

function sgDashIcon(string $title): string {
    $t = strtolower($title);
    if (str_contains($t, 'perawat') || str_contains($t, 'nurse')) return 'fa-user-nurse';
    if (str_contains($t, 'bidan'))                                  return 'fa-baby';
    if (str_contains($t, 'apoteker') || str_contains($t, 'farmasi')) return 'fa-prescription-bottle-medical';
    if (str_contains($t, 'admin') || str_contains($t, 'rekam'))    return 'fa-folder';
    if (str_contains($t, 'hr') || str_contains($t, 'rekrut'))      return 'fa-handshake';
    if (str_contains($t, 'marketing'))                              return 'fa-chart-line';
    if (str_contains($t, 'it') || str_contains($t, 'teknologi'))   return 'fa-laptop-code';
    if (str_contains($t, 'akunt') || str_contains($t, 'pajak'))    return 'fa-calculator';
    return 'fa-briefcase';
}
@endphp

{{-- Flash messages --}}
@if(session('success'))
<div class="mb-5 flex items-start gap-3 rounded-[14px] px-5 py-4 border border-green-200 bg-green-600/[0.07]">
  <i class="fa-solid fa-circle-check text-green-600 mt-px"></i>
  <span class="text-[13.5px] font-semibold text-green-700">{{ session('success') }}</span>
</div>
@endif
@if(session('warning'))
<div class="mb-5 flex items-start gap-3 rounded-[14px] px-5 py-4 border border-amber-200 bg-amber-500/[0.07]">
  <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-px"></i>
  <span class="text-[13.5px] font-semibold text-amber-700">{{ session('warning') }}</span>
</div>
@endif

{{-- Greeting --}}
<div class="relative overflow-hidden rounded-[20px] text-white px-7 py-6"
     style="background:linear-gradient(135deg,#042c6c 0%,#0b47a1 100%);">
  <div class="absolute w-[200px] h-[200px] rounded-full -right-[50px] -top-[90px] pointer-events-none bg-white/[0.05]"></div>
  <div class="relative z-[1]">
    <div class="text-[11.5px] font-bold tracking-[0.08em] uppercase text-white/70">Selamat datang</div>
    <h1 class="mt-1 mb-1 text-2xl font-extrabold tracking-[-0.5px]">{{ auth()->user()->name }}</h1>
    <div class="text-[13px] text-white/70">{{ now()->translatedFormat('l, j F Y') }}</div>
  </div>
</div>

{{-- Active application widget --}}
@if($activeApp)
<div class="mt-5 rounded-[18px] border border-slate-200 bg-white overflow-hidden">
  <div class="px-6 py-[14px] border-b border-slate-100 flex items-center justify-between">
    <div class="text-[12px] font-bold uppercase tracking-[0.06em] text-slate-500">
      <i class="fa-solid fa-circle-dot mr-1.5 text-sgg-green text-[10px]"></i>Lamaran Sedang Berjalan
    </div>
    <a href="{{ route('lamaran.show', $activeApp) }}"
       class="text-[12.5px] font-bold text-navy no-underline hover:underline">Lihat detail →</a>
  </div>
  <div class="px-6 py-5">
    <div class="flex flex-wrap gap-3 items-start justify-between">
      <div>
        <div class="text-[17px] font-extrabold text-slate-800 tracking-[-0.3px]">{{ $activeApp->jobOpening->title }}</div>
        <div class="mt-1 text-[13px] font-semibold text-slate-500">{{ $activeApp->jobOpening->company_name }}</div>
      </div>
      <span class="badge {{ $statusMap[$activeApp->status]['badge'] }} mt-0.5">
        <i class="fa-solid {{ $statusMap[$activeApp->status]['icon'] }}"></i>
        {{ $statusMap[$activeApp->status]['label'] }}
      </span>
    </div>

    {{-- Mini timeline --}}
    @php
      $tlSteps = [
        ['label' => 'Lamaran Masuk', 'icon' => 'fa-paper-plane'],
        ['label' => 'Screening',      'icon' => 'fa-magnifying-glass'],
        ['label' => 'Wawancara',      'icon' => 'fa-comments'],
        ['label' => 'Keputusan',      'icon' => 'fa-award'],
      ];
      $curStep = match($activeApp->status) {
        'applied' => 0, 'screening' => 1, 'interview' => 2, 'passed', 'rejected' => 3, default => 0
      };
    @endphp
    <div class="mt-5 pt-5 border-t border-slate-100" style="display:flex;gap:0;">
      @foreach($tlSteps as $i => $s)
      <div style="flex:1;text-align:center;position:relative;padding:0 4px;">
        @if($i > 0)
        <div style="position:absolute;top:13px;left:0;right:50%;height:2px;background:{{ $i <= $curStep ? '#042c6c' : '#e2e8f0' }};"></div>
        @endif
        @if($i < 3)
        <div style="position:absolute;top:13px;left:50%;right:0;height:2px;background:{{ $i < $curStep ? '#042c6c' : '#e2e8f0' }};"></div>
        @endif
        <div style="width:26px;height:26px;border-radius:50%;margin:0 auto;position:relative;z-index:1;display:flex;align-items:center;justify-content:center;font-size:10px;
          {{ $i <= $curStep ? 'background:#042c6c;color:#fff;' : 'background:#f1f5f9;color:#94a3b8;border:2px solid #e2e8f0;' }}">
          <i class="fa-solid {{ $i < $curStep ? 'fa-check' : $s['icon'] }}"></i>
        </div>
        <div style="margin-top:5px;font-size:10.5px;font-weight:700;color:{{ $i <= $curStep ? '#042c6c' : '#94a3b8' }};">{{ $s['label'] }}</div>
      </div>
      @endforeach
    </div>
    <div class="mt-3 text-[12px] text-slate-400">Dikirim {{ $activeApp->created_at->translatedFormat('j F Y') }}</div>
  </div>
</div>
@endif

{{-- Lowongan Terbuka --}}
<div id="lowongan" class="mt-7">
  <div class="flex items-baseline justify-between mb-4">
    <h2 class="m-0 text-[17px] font-extrabold text-slate-800 tracking-[-0.3px]">Lowongan Terbuka</h2>
    <span class="text-[13px] text-slate-500">{{ $openings->count() }} posisi</span>
  </div>

  @if($openings->isEmpty())
  <div class="rounded-[16px] border border-dashed border-slate-300 bg-white px-6 py-8 text-center">
    <div class="text-[13.5px] font-semibold text-slate-500">Belum ada lowongan yang dibuka saat ini.</div>
  </div>
  @else
  <div class="sg-job-grid">
    @foreach($openings as $opening)
    <article class="sg-job-card">
      <div class="flex gap-[14px] items-start">
        <div class="w-[42px] h-[42px] shrink-0 rounded-[13px] grid place-items-center text-[16px] text-navy bg-navy/[0.08]">
          <i class="fa-solid {{ sgDashIcon($opening->title) }}"></i>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex flex-wrap gap-1.5 items-center">
            <h3 class="m-0 text-[15px] font-bold text-slate-800 leading-[1.3]">{{ $opening->title }}</h3>
            <span class="px-[9px] py-[3px] rounded-full text-[11px] font-bold text-sgg-green whitespace-nowrap bg-sgg-green/[0.1]">Full Time</span>
          </div>
          <div class="mt-0.5 text-[13px] font-semibold text-slate-600">{{ $opening->company_name }}</div>
        </div>
      </div>
      @if($opening->description)
      <div class="mt-3 text-[13px] text-slate-500 leading-normal line-clamp-2">{{ $opening->description }}</div>
      @endif
      <div class="sg-job-card-footer">
        <span class="text-[12px] text-slate-500">
          <i class="fa-solid fa-users text-slate-400 mr-[5px]"></i>Kuota: {{ $opening->quota }} orang
        </span>
        <div style="display:flex;gap:8px;">
          <a href="{{ route('lowongan.show', $opening) }}" class="sg-job-card-btn" style="background:#f1f5f9;color:#042c6c;">Detail</a>
          @if($activeApp)
            <span class="sg-job-card-btn" style="background:#e2e8f0;color:#94a3b8;cursor:not-allowed;">Melamar</span>
          @else
            <a href="{{ route('lamaran.create', $opening) }}" class="sg-job-card-btn">Lamar</a>
          @endif
        </div>
      </div>
    </article>
    @endforeach
  </div>
  @endif
</div>

{{-- Riwayat Lamaran (hanya yang selesai) --}}
@if($pastApps->isNotEmpty())
<div class="mt-8">
  <div class="flex items-baseline justify-between mb-4">
    <h2 class="m-0 text-[17px] font-extrabold text-slate-800 tracking-[-0.3px]">Riwayat Lamaran</h2>
    <span class="text-[13px] text-slate-500">{{ $pastApps->count() }} lamaran</span>
  </div>
  <div class="flex flex-col gap-3">
    @foreach($pastApps as $app)
    <a href="{{ route('lamaran.show', $app) }}"
       class="no-underline flex items-center gap-4 bg-white border border-slate-200 rounded-[16px] px-5 py-4 hover:border-navy transition-colors duration-150">
      <div class="w-[40px] h-[40px] shrink-0 rounded-[12px] grid place-items-center text-slate-400 bg-slate-100 text-[15px]">
        <i class="fa-solid fa-file-lines"></i>
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-[14px] font-bold text-slate-800 truncate">{{ $app->jobOpening->title }}</div>
        <div class="text-[12.5px] text-slate-500 mt-0.5">
          {{ $app->jobOpening->company_name }} · {{ $app->created_at->translatedFormat('j M Y') }}
        </div>
      </div>
      <div class="shrink-0 flex items-center gap-3">
        <span class="badge {{ $statusMap[$app->status]['badge'] }}">
          {{ $statusMap[$app->status]['label'] }}
        </span>
        <i class="fa-solid fa-chevron-right text-[11px] text-slate-300 sg-desk"></i>
      </div>
    </a>
    @endforeach
  </div>
</div>
@endif

</x-app-layout>
