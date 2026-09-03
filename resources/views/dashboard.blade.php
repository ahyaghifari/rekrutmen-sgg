<x-app-layout>
<x-slot name="title">Dashboard — Syifa Global Group</x-slot>

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
// ponytail: kalau tidak ada lamaran yang sedang berjalan, tetap tampilkan timeline untuk lamaran terakhir (meski sudah selesai/ditolak).
$activeApp = $applications->first(fn($a) => in_array($a->status, ['applied','screening','shortlisted','interview','offering','mcu','onboarding']))
    ?? $applications->first();
$pastApps  = $applications->filter(fn($a) => in_array($a->status, ['passed','rejected']) && (!$activeApp || $a->isNot($activeApp)));


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

{{-- Ucapan selamat diterima --}}
@if($activeApp && $activeApp->status === 'passed')
<div class="relative overflow-hidden rounded-[20px] text-white px-7 py-6 mt-5"
     style="background:linear-gradient(135deg,#047C54 0%,#024732 100%);"
     x-data
     x-init="
       const canvas = document.createElement('canvas');
       canvas.style.cssText = 'position:fixed;inset:0;width:100vw;height:100vh;pointer-events:none;z-index:9999;';
       canvas.width = window.innerWidth; canvas.height = window.innerHeight;
       document.body.appendChild(canvas);
       const ctx = canvas.getContext('2d');
       const colors = ['#047C54','#042c6c','#B9308C','#f59e0b','#fff'];
       const pieces = Array.from({ length: 140 }, () => ({
         x: Math.random() * canvas.width,
         y: -20 - Math.random() * canvas.height * 0.5,
         w: 6 + Math.random() * 6,
         h: 8 + Math.random() * 8,
         color: colors[Math.floor(Math.random() * colors.length)],
         speed: 2 + Math.random() * 3,
         drift: (Math.random() - 0.5) * 2,
         rotation: Math.random() * 360,
         spin: (Math.random() - 0.5) * 10,
       }));
       const start = performance.now();
       function tick(now) {
         ctx.clearRect(0, 0, canvas.width, canvas.height);
         pieces.forEach(p => {
           p.y += p.speed; p.x += p.drift; p.rotation += p.spin;
           ctx.save();
           ctx.translate(p.x, p.y);
           ctx.rotate(p.rotation * Math.PI / 180);
           ctx.fillStyle = p.color;
           ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
           ctx.restore();
         });
         if (now - start < 3500) {
           requestAnimationFrame(tick);
         } else {
           canvas.remove();
         }
       }
       requestAnimationFrame(tick);
     ">
  <div class="absolute w-[200px] h-[200px] rounded-full -right-[50px] -top-[90px] pointer-events-none bg-white/[0.06]"></div>
  <div class="relative z-[1] flex items-start gap-4">
    <div class="w-11 h-11 shrink-0 rounded-full grid place-items-center bg-white/[0.14]">
      <i class="fa-solid fa-trophy text-lg"></i>
    </div>
    <div>
      <h2 class="m-0 mb-1 text-xl font-extrabold tracking-[-0.4px]">Selamat, kamu diterima!</h2>
      <p class="m-0 text-[13.5px] text-white/85">Kamu resmi bergabung sebagai <strong>{{ $activeApp->jobOpening->title }}</strong> di {{ $activeApp->jobOpening->company_name }}. Sampai jumpa di hari pertama kerja!</p>
    </div>
  </div>
</div>
@endif

{{-- Active application widget --}}
@if($activeApp)
<div class="sg-card mt-5 rounded-[18px] overflow-hidden">
  <div class="sg-dd-header px-6 py-[14px] border-b border-slate-100 flex items-center justify-between">
    @php
      $activeAppIsFinal = in_array($activeApp->status, ['passed', 'rejected']);
    @endphp
    <div class="text-[12px] font-bold uppercase tracking-[0.06em] text-slate-500">
      <i class="fa-solid fa-circle-dot mr-1.5 {{ $activeAppIsFinal ? 'text-slate-400' : 'text-sgg-green' }} text-[10px]"></i>{{ $activeAppIsFinal ? 'Lamaran Terakhir' : 'Lamaran Sedang Berjalan' }}
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
      $tlBrand      = $activeApp->jobOpening->company?->brand_color ?? '#042c6c';
      $tlRequiresMcu = $activeApp->jobOpening->company?->requires_mcu ?? true;

      $tlSteps = [
        ['status' => 'applied',     'label' => 'Lamaran Masuk',     'icon' => 'fa-paper-plane',      'sub' => 'Formulir terkirim'],
        ['status' => 'screening',   'label' => 'Peninjauan Berkas', 'icon' => 'fa-magnifying-glass', 'sub' => 'Tinjauan awal'],
        ['status' => 'shortlisted', 'label' => 'Tes',        'icon' => 'fa-laptop',           'sub' => 'Kerjakan tes'],
        ['status' => 'interview',   'label' => 'Wawancara',         'icon' => 'fa-comments',         'sub' => 'Jadwal dikirim'],
        ['status' => 'offering',    'label' => 'Offering Letter',   'icon' => 'fa-file-signature',   'sub' => 'Surat penawaran'],
      ];
      if ($tlRequiresMcu) {
        $tlSteps[] = ['status' => 'mcu', 'label' => 'MCU', 'icon' => 'fa-stethoscope', 'sub' => 'Medical check up'];
      }
      $tlSteps[] = ['status' => 'onboarding', 'label' => 'TTD & Onboarding', 'icon' => 'fa-signature', 'sub' => 'Kontrak & orientasi'];

      $tlIsRejected = $activeApp->status === 'rejected';
      if ($activeApp->status === 'passed') {
          $curStep = count($tlSteps);
      } elseif ($tlIsRejected) {
          $tlRejectedFromStatus = $activeApp->statusLogs()->where('to_status', 'rejected')->latest('changed_at')->first()?->from_status;
          $curStep = collect($tlSteps)->search(fn ($s) => $s['status'] === $tlRejectedFromStatus) ?: 0;
      } else {
          $curStep = collect($tlSteps)->search(fn ($s) => $s['status'] === $activeApp->status);
          if ($curStep === false) $curStep = 0;
      }
      $lastIdx = count($tlSteps) - 1;
      $tlCols  = 3;
    @endphp
    <div class="sg-tl-widget sg-tl-widget-snake mt-5 pt-5 sg-dd-border border-t">
      @foreach($tlSteps as $i => $s)
      @php
        $done         = $i < $curStep;
        $current      = $i === $curStep;
        $future       = $i > $curStep;
        $tlActiveColor = ($tlIsRejected && $current) ? '#dc2626' : $tlBrand;

        $row        = intdiv($i, $tlCols);
        $posInRow   = $i % $tlCols;
        $rowIsEven  = $row % 2 === 0;
        $col        = $rowIsEven ? $posInRow + 1 : $tlCols - $posInRow;
        $isLastInRow = $posInRow === $tlCols - 1;
        $hasNext     = $i < $lastIdx;
      @endphp
      <div style="flex:1;text-align:center;position:relative;padding:0 4px;grid-column:{{ $col }};grid-row:{{ $row + 1 }};">
        {{-- Garis desktop: baris tunggal, lurus kiri-kanan --}}
        @if($i > 0)
        <div class="sg-tl-line-desktop {{ $done || $current ? '' : 'sg-tl-track-todo' }}"
             style="position:absolute;top:19px;left:0;right:50%;height:2px;{{ $done || $current ? 'background:'.$tlBrand : '' }};"></div>
        @endif
        @if($hasNext)
        <div class="sg-tl-line-desktop {{ $done ? '' : 'sg-tl-track-todo' }}"
             style="position:absolute;top:19px;left:50%;right:0;height:2px;{{ $done ? 'background:'.$tlBrand : '' }};"></div>
        @endif

        {{-- Garis mobile: susun ular kanan-turun-kiri --}}
        @if($hasNext && ! $isLastInRow)
        <div class="sg-tl-line-mobile {{ $done ? '' : 'sg-tl-track-todo' }}"
             style="position:absolute;top:19px;height:2px;{{ $rowIsEven ? 'left:calc(50% + 25px);right:calc(-50% + 25px);' : 'right:calc(50% + 25px);left:calc(-50% + 25px);' }}{{ $done ? 'background:'.$tlBrand : '' }};"></div>
        @endif
        @if($hasNext && $isLastInRow)
        <div class="sg-tl-vline-mobile {{ $done ? '' : 'sg-tl-track-todo' }}"
             style="position:absolute;left:50%;top:100%;margin-left:-1px;width:2px;{{ $done ? 'background:'.$tlBrand : '' }};"></div>
        @endif

        <div class="{{ $future ? 'sg-tl-node-todo' : '' }}"
             style="width:38px;height:38px;border-radius:50%;margin:0 auto;position:relative;z-index:1;display:flex;align-items:center;justify-content:center;font-size:12px;{{ !$future ? 'background:'.$tlActiveColor.';color:#fff;' : '' }}">
          @if($done)
            <i class="fa-solid fa-check"></i>
          @elseif($current && $tlIsRejected)
            <i class="fa-solid fa-xmark"></i>
          @else
            <i class="fa-solid {{ $s['icon'] }}"></i>
          @endif
        </div>
        <div class="{{ $future ? 'sg-tl-label-todo' : '' }}"
             style="margin-top:7px;font-size:11px;font-weight:700;line-height:1.3;{{ !$future ? 'color:'.$tlActiveColor : '' }};">{{ $s['label'] }}</div>
        <div class="sg-tl-sub">{{ $s['sub'] }}</div>
      </div>
      @endforeach
    </div>
    <div class="mt-3 text-[12px] text-slate-400">Dikirim {{ $activeApp->created_at->translatedFormat('j F Y') }}</div>

    @if($activeApp->status === 'shortlisted' && ! $activeApp->quiz_result)
      @if($activeApp->quiz_sent_at)
      <a href="{{ route('tes-online.show') }}"
         class="mt-4 flex items-center justify-center gap-2 w-full py-[12px] rounded-[12px] text-white text-[13.5px] font-bold no-underline transition-opacity duration-150 hover:opacity-90"
         style="background:{{ $tlBrand }};">
        <i class="fa-solid fa-laptop text-xs"></i>Laksanakan Tes
      </a>
      @else
      <div class="mt-4 flex items-start gap-2.5 rounded-[12px] px-4 py-3 bg-amber-50 border border-amber-200">
        <i class="fa-solid fa-circle-info text-amber-500 mt-px text-xs"></i>
        <span class="text-[13px] text-amber-700">Kuis belum dipublikasikan/diaktifkan oleh tim rekrutmen. Tunggu pemberitahuan selanjutnya sebelum bisa mengerjakan tes.</span>
      </div>
      @endif
    @endif

    @if($activeApp->status === 'interview')
    @php $interviewSchedule = $activeApp->interviewInvitee?->schedule; @endphp
    <div class="mt-4 flex items-start gap-2.5 rounded-[12px] px-4 py-3" style="background:{{ $tlBrand }}14;border:1px solid {{ $tlBrand }}33;">
      <i class="fa-solid fa-calendar-days mt-px text-xs" style="color:{{ $tlBrand }};"></i>
      <div class="text-[13px]" style="color:{{ $tlBrand }};">
        @if($interviewSchedule)
          <strong>Jadwal Wawancara:</strong>
          {{ $interviewSchedule->interview_date?->translatedFormat('l, j F Y') }}
          @if($interviewSchedule->interview_time) &middot; {{ substr($interviewSchedule->interview_time, 0, 5) }} WIB @endif
          @if($interviewSchedule->location) <br>Lokasi: {{ $interviewSchedule->location }} @endif
          @if($interviewSchedule->notes) <br>Catatan: {{ $interviewSchedule->notes }} @endif
        @else
          Jadwal wawancara akan diinformasikan oleh tim rekrutmen. Tunggu pemberitahuan selanjutnya.
        @endif
      </div>
    </div>
    @endif

    @if($activeApp->status === 'mcu')
    @php $mcuSchedule = $activeApp->mcuInvitee?->schedule; @endphp
    <div class="mt-4 flex items-start gap-2.5 rounded-[12px] px-4 py-3" style="background:{{ $tlBrand }}14;border:1px solid {{ $tlBrand }}33;">
      <i class="fa-solid fa-calendar-days mt-px text-xs" style="color:{{ $tlBrand }};"></i>
      <div class="text-[13px]" style="color:{{ $tlBrand }};">
        @if($mcuSchedule)
          <strong>Jadwal Medical Check Up:</strong>
          {{ $mcuSchedule->mcu_date?->translatedFormat('l, j F Y') }}
          @if($mcuSchedule->mcu_time) &middot; {{ substr($mcuSchedule->mcu_time, 0, 5) }} WIB @endif
          @if($mcuSchedule->location) <br>Lokasi: {{ $mcuSchedule->location }} @endif
          @if($mcuSchedule->notes) <br>Catatan: {{ $mcuSchedule->notes }} @endif
        @else
          Jadwal MCU akan diinformasikan oleh tim rekrutmen. Tunggu pemberitahuan selanjutnya.
        @endif
      </div>
    </div>
    @endif

    @if($activeApp->status === 'offering')
    <div class="mt-4 pt-4 sg-dd-border border-t">
      @if(! $activeApp->offering_letter_path)
      <div class="text-[13px] text-slate-500">Surat penawaran sedang disiapkan tim rekrutmen. Cek lagi nanti ya.</div>
      @else
        <a href="{{ route('offering-letter.unduh') }}" target="_blank"
           class="flex items-center justify-center gap-2 w-full py-[12px] rounded-[12px] text-white text-[13.5px] font-bold no-underline transition-opacity duration-150 hover:opacity-90"
           style="background:{{ $tlBrand }};">
          <i class="fa-solid fa-download text-xs"></i>Unduh Surat Penawaran
        </a>

        @php($negos = $activeApp->offeringNegotiations)
        @if($negos->isNotEmpty())
        <div class="mt-4 space-y-2">
          <div class="text-[12.5px] font-bold text-slate-700">Riwayat Negosiasi</div>
          @foreach($negos as $n)
          <div class="rounded-xl px-3 py-2 text-[12.5px] {{ $n->sender === 'applicant' ? 'bg-slate-100 text-slate-700' : 'bg-navy/5 text-navy' }}">
            <div class="font-semibold">{{ $n->sender === 'applicant' ? 'Kamu' : 'Tim Rekrutmen' }} &middot; <span class="font-normal text-slate-400">{{ $n->created_at->translatedFormat('j M Y, H:i') }}</span></div>
            <div class="mt-0.5 whitespace-pre-line">{{ $n->message }}</div>
          </div>
          @endforeach
        </div>
        @endif

        @if($activeApp->offering_letter_signed_path)
        <div class="mt-3 flex items-center gap-2 text-[13px] text-sgg-green font-semibold">
          <i class="fa-solid fa-circle-check"></i>Surat bertanda tangan sudah diunggah {{ $activeApp->offering_letter_signed_at->translatedFormat('j F Y, H:i') }}.
        </div>
        @else
        <details class="mt-3 group">
          <summary class="cursor-pointer text-[13px] font-semibold text-navy hover:underline list-none">
            <i class="fa-solid fa-comments text-xs"></i> Ada yang ingin dinegosiasikan dulu?
          </summary>
          <form method="POST" action="{{ route('offering-letter.negosiasi') }}" class="mt-2" x-data="{ sending: false }" @submit="sending = true">
            @csrf
            <textarea name="message" rows="3" required maxlength="2000"
                      placeholder="Tuliskan poin yang ingin dinegosiasikan (mis. gaji, tanggal mulai, tunjangan)..."
                      class="w-full rounded-[10px] border border-slate-300 px-3 py-2 text-[13px] focus:border-navy focus:outline-none"></textarea>
            <button type="submit" :disabled="sending"
                    class="mt-2 px-4 py-[9px] rounded-[10px] border-0 bg-slate-700 hover:bg-slate-800 text-white text-[13px] font-bold cursor-pointer disabled:opacity-50">
              <span x-show="!sending">Kirim Pengajuan Negosiasi</span>
              <span x-show="sending">Mengirim...</span>
            </button>
          </form>
        </details>

        <form method="POST" action="{{ route('offering-letter.upload-signed') }}" enctype="multipart/form-data" class="mt-3"
              x-data="{ fileName: null, dragging: false, submitting: false }"
              @submit="submitting = true">
          @csrf
          <label class="block mb-[7px] text-[12.5px] font-bold text-slate-700">Unggah Surat yang Sudah Ditandatangani</label>
          <label class="sg-upload-area block border-2 border-dashed rounded-2xl py-6 px-5 text-center cursor-pointer hover:border-navy transition-colors duration-150"
                 :class="dragging ? 'border-navy' : 'border-slate-300'"
                 @dragover.prevent="dragging = true"
                 @dragleave.prevent="dragging = false"
                 @drop.prevent="dragging = false; $refs.signedFile.files = $event.dataTransfer.files; fileName = $event.dataTransfer.files[0]?.name">
            <i class="fa-solid fa-file-arrow-up text-[22px] text-slate-400"></i>
            <div class="mt-2.5 text-sm font-bold text-slate-800" x-text="fileName || 'Seret file ke sini, atau klik untuk pilih...'"></div>
            <div class="mt-1 text-[12.5px] text-slate-500">Format: PDF, DOC, DOCX &middot; Maks. 5MB</div>
            <input type="file" name="file" x-ref="signedFile" accept=".pdf,.doc,.docx" required class="sr-only"
                   @change="fileName = $event.target.files[0]?.name" />
          </label>
          <button type="submit" :disabled="submitting"
                  class="mt-3 w-full px-4 py-[10px] rounded-[10px] border-0 bg-navy hover:bg-navy-dark text-white text-[13px] font-bold cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
            <span x-show="! submitting">Unggah</span>
            <span x-show="submitting">Mengunggah...</span>
          </button>
        </form>
        @endif
      @endif
    </div>
    @endif
  </div>
</div>
@endif

{{-- Lowongan Terbuka --}}
@unless($activeApp && $activeApp->status === 'passed')
<div id="lowongan" class="mt-7">
  <div class="flex items-baseline justify-between mb-4">
    <h2 class="m-0 text-[17px] font-extrabold text-slate-800 tracking-[-0.3px]">Lowongan Terbuka</h2>
    <span class="text-[13px] text-slate-500">{{ $openings->count() }} posisi</span>
  </div>

  @if($openings->isEmpty())
  <div class="sg-card rounded-[16px] px-6 py-8 text-center">
    <div class="w-11 h-11 rounded-full grid place-items-center mx-auto mb-2.5" style="background:rgba(4,44,108,.07);">
      <i class="fa-solid fa-briefcase text-navy text-[17px]"></i>
    </div>
    <div class="text-[13.5px] font-semibold text-slate-500">Belum ada lowongan yang dibuka saat ini.</div>
  </div>
  @else
  <div class="sg-job-grid">
    @foreach($openings as $opening)
      <x-job-card :opening="$opening" :index="$loop->index" :hasActiveApp="(bool) $activeApp" />
    @endforeach
  </div>
  @endif
</div>
@endunless

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
       class="sg-card no-underline flex items-center gap-4 rounded-[16px] px-5 py-4 hover:border-navy transition-colors duration-150">
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
