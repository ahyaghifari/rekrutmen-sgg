<x-public-layout>
<x-slot name="title">{{ $jobOpening->title }} — Syifa Global Group</x-slot>

@php $logo = $co?->logo_path ? asset('storage/'.$co->logo_path) : null; @endphp

<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:rounded-lg" style="background:{{ $brand }};color:#fff;">Langsung ke konten</a>

<div class="sg-rise min-h-screen">

  {{-- Top gradient bar --}}
  <div class="h-1" style="background:linear-gradient(90deg,{{ $brand }} 0%,{{ $brandGrad }} 55%,{{ $brandDark }} 100%);" aria-hidden="true"></div>

  {{-- Navbar --}}
  <header class="sg-navbar-header border-b">
    <div class="max-w-[1180px] mx-auto px-6 py-[14px] flex items-center gap-7">
      <div class="flex items-center gap-3">
        <img src="/logo-sgg.png" alt="Syifa Global Group" class="h-9 w-auto" width="36" height="36" />
        <div class="text-[11px] font-semibold tracking-[0.06em] uppercase text-slate-400">Portal Rekrutmen</div>
      </div>

      {{-- Desktop nav --}}
      <nav class="sg-desk-flex gap-1.5 ml-auto items-center" aria-label="Navigasi utama">
        <a href="{{ route('welcome') }}" class="px-[14px] py-2 rounded-[10px] text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors duration-150">Beranda</a>
        <a href="{{ route('welcome') }}#lowongan" class="px-[14px] py-2 rounded-[10px] text-sm font-semibold text-slate-800" style="background:rgba({{ $r }},{{ $g }},{{ $b }},0.09);color:{{ $brand }};" aria-current="page">Lowongan</a>
        @auth
          <a href="{{ route('dashboard') }}" class="ml-2 px-5 py-[10px] rounded-[11px] text-white text-sm font-bold no-underline transition-colors duration-150" style="background:{{ $brand }};">Dashboard</a>
        @else
          <a href="{{ route('login') }}" class="ml-2 px-5 py-[10px] rounded-[11px] text-white text-sm font-bold no-underline transition-colors duration-150" style="background:{{ $brand }};">Masuk</a>
        @endauth
        <button onclick="sgToggleTheme()" aria-label="Ganti tema gelap/terang" class="ml-2 w-9 h-9 rounded-[10px] border border-slate-200 grid place-items-center text-slate-500 hover:border-slate-300 transition-colors duration-150" style="touch-action:manipulation;">
          <i class="fa-solid fa-moon sg-icon-dark" aria-hidden="true"></i>
          <i class="fa-solid fa-sun sg-icon-light" aria-hidden="true" style="display:none;"></i>
        </button>
      </nav>

      {{-- Mobile --}}
      <div class="sg-mob ml-auto" style="display:flex;align-items:center;gap:8px;">
        @auth
          <a href="{{ route('dashboard') }}" class="px-4 py-[9px] rounded-[11px] text-white text-[13.5px] font-bold no-underline" style="background:{{ $brand }};">Dashboard</a>
        @else
          <a href="{{ route('login') }}" class="px-4 py-[9px] rounded-[11px] text-white text-[13.5px] font-bold no-underline" style="background:{{ $brand }};">Masuk</a>
        @endauth
        <button onclick="sgToggleTheme()" aria-label="Ganti tema" class="w-9 h-9 rounded-[10px] border border-slate-200 grid place-items-center text-slate-500" style="touch-action:manipulation;">
          <i class="fa-solid fa-moon sg-icon-dark" aria-hidden="true"></i>
          <i class="fa-solid fa-sun sg-icon-light" aria-hidden="true" style="display:none;"></i>
        </button>
      </div>
    </div>
  </header>

  {{-- Main --}}
  <main id="main-content" class="max-w-[1180px] mx-auto px-6 pt-8 pb-[72px]">

    {{-- Back --}}
    <a href="{{ route('welcome') }}#lowongan"
       class="inline-flex items-center gap-2 mb-6 text-[13px] font-semibold text-slate-500 no-underline transition-colors duration-150"
       style="hover-color:{{ $brand }};"
       onmouseover="this.style.color='{{ $brand }}'" onmouseout="this.style.color=''">
      <i class="fa-solid fa-arrow-left text-[11px]"></i>Semua Lowongan
    </a>

    {{-- Hero card --}}
    <div class="relative overflow-hidden rounded-[20px] text-white px-6 py-6"
         style="background:linear-gradient(135deg,{{ $brand }} 0%,{{ $brandDark }} 100%);">
      <div class="absolute w-[260px] h-[260px] rounded-full -right-[70px] -top-[120px] pointer-events-none bg-white/[0.05]"></div>
      <div class="absolute w-[120px] h-[120px] rounded-[30px] rotate-[22deg] right-[180px] -bottom-[70px] pointer-events-none bg-white/[0.08]"></div>
      <div class="relative z-[1]">
        {{-- Logo + company --}}
        <div class="flex items-center gap-3 mb-3">
          <div class="w-[48px] h-[48px] shrink-0 rounded-[14px] grid place-items-center overflow-hidden bg-white/[0.18]">
            @if($logo)
              <img src="{{ $logo }}" alt="{{ $co->name }}" class="w-full h-full object-contain p-[8px]">
            @else
              <i class="fa-solid {{ sgJobIcon($jobOpening->title) }} text-[20px]"></i>
            @endif
          </div>
          <div class="text-[12px] font-bold tracking-[0.07em] uppercase text-white/75 leading-tight">
            {{ $co?->name ?? $jobOpening->company_name }}
          </div>
        </div>
        {{-- Title --}}
        <h1 class="m-0 mb-4 text-[22px] font-extrabold tracking-[-0.5px] leading-[1.2]">{{ $jobOpening->title }}</h1>
        {{-- Chips --}}
        <div class="flex flex-wrap gap-2">
          <div class="flex items-center gap-2 px-3 py-[7px] rounded-[10px] bg-white/[0.12] text-[12.5px] font-semibold">
            <i class="fa-solid fa-users text-white/70 text-[11px]"></i>{{ $jobOpening->quota }} posisi
          </div>
          <div class="flex items-center gap-2 px-3 py-[7px] rounded-[10px] bg-white/[0.12] text-[12.5px] font-semibold">
            <i class="fa-solid fa-clock text-white/70 text-[11px]"></i>Full Time
          </div>
          @if($jobOpening->applications_deadline)
          <div class="flex items-center gap-2 px-3 py-[7px] rounded-[10px] bg-white/[0.12] text-[12.5px] font-semibold">
            <i class="fa-solid fa-calendar-xmark text-white/70 text-[11px]"></i>Ditutup {{ $jobOpening->applications_deadline->translatedFormat('d M Y') }}
          </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Content grid --}}
    <div class="sg-2col mt-6">

      {{-- Left: detail --}}
      <div class="flex flex-col gap-4">

        @if($jobOpening->description)
        <div class="sg-card px-7 py-6" style="border-top:3px solid {{ $brand }};">
          <h2 class="m-0 mb-4 text-[16px] font-extrabold text-slate-800 tracking-[-0.3px]">
            <i class="fa-solid fa-file-lines mr-2 text-[14px]" style="color:{{ $brand }};"></i>Deskripsi Pekerjaan
          </h2>
          <div class="text-[14px] leading-[1.75] text-slate-600 rich-content">{!! $jobOpening->description !!}</div>
        </div>
        @endif

        @if($jobOpening->requirements)
        <div class="sg-card px-7 py-6" style="border-top:3px solid {{ $brandDark }};">
          <h2 class="m-0 mb-4 text-[16px] font-extrabold text-slate-800 tracking-[-0.3px]">
            <i class="fa-solid fa-list-check mr-2 text-[14px]" style="color:{{ $brandDark }};"></i>Kualifikasi &amp; Persyaratan
          </h2>
          <div class="text-[14px] leading-[1.75] text-slate-600 rich-content">{!! $jobOpening->requirements !!}</div>
        </div>
        @endif

        {{-- Alur seleksi --}}
        <div class="sg-card px-7 py-6" style="border-top:3px solid rgba({{ $r }},{{ $g }},{{ $b }},0.5);">
          <h2 class="m-0 mb-5 text-[16px] font-extrabold text-slate-800 tracking-[-0.3px]">
            <i class="fa-solid fa-route mr-2 text-[13px]" style="color:{{ $brand }};"></i>Alur Seleksi
          </h2>
          <div class="flex flex-col gap-3">
            @foreach([
              ['icon' => 'fa-paper-plane',     'label' => 'Kirim Lamaran',    'desc' => 'Isi formulir dan unggah CV'],
              ['icon' => 'fa-magnifying-glass', 'label' => 'Seleksi Berkas',   'desc' => 'Tim HR meninjau dokumen'],
              ['icon' => 'fa-comments',         'label' => 'Wawancara',        'desc' => 'Jadwal dikirim via WhatsApp'],
              ['icon' => 'fa-circle-check',     'label' => 'Keputusan',        'desc' => 'Offering letter & onboarding'],
            ] as $s)
            <div class="flex items-center gap-4">
              <div class="w-9 h-9 shrink-0 rounded-[11px] grid place-items-center text-[13px]"
                   style="background:rgba({{ $r }},{{ $g }},{{ $b }},0.09);color:{{ $brand }};">
                <i class="fa-solid {{ $s['icon'] }}"></i>
              </div>
              <div>
                <div class="text-[13.5px] font-bold text-slate-800">{{ $s['label'] }}</div>
                <div class="text-[12px] text-slate-500">{{ $s['desc'] }}</div>
              </div>
            </div>
            @endforeach
          </div>
        </div>

      </div>

      {{-- Right: sidebar --}}
      <div class="flex flex-col gap-4">

        {{-- Apply CTA --}}
        <div class="sg-card px-6 py-6 sticky top-[74px]">
          <div class="text-[12px] font-bold uppercase tracking-[0.06em] text-slate-500 mb-3">Lamar Sekarang</div>
          <div class="text-[15px] font-extrabold text-slate-800 leading-[1.3] mb-1">{{ $jobOpening->title }}</div>
          <div class="text-[13px] text-slate-500 mb-5">{{ $co?->name ?? $jobOpening->company_name }}</div>

          @auth
            @php $hasActive = \App\Models\JobApplication::hasActiveApplication(auth()->id()); @endphp
            @if($hasActive)
              <div class="w-full py-[13px] rounded-[12px] text-center text-[13.5px] font-bold text-slate-400 bg-slate-100 cursor-not-allowed">
                Kamu sudah memiliki lamaran aktif
              </div>
              <a href="{{ route('dashboard') }}" class="mt-3 block text-center text-[12.5px] font-semibold no-underline hover:underline" style="color:{{ $brand }};">
                Pantau status lamaran →
              </a>
            @else
              <a href="{{ route('lamaran.create', $jobOpening) }}"
                 class="block w-full py-[13px] rounded-[12px] text-center text-[13.5px] font-bold text-white no-underline transition-opacity duration-150 hover:opacity-90"
                 style="background:{{ $brand }};">
                <i class="fa-solid fa-paper-plane mr-2 text-xs"></i>Kirim Lamaran
              </a>
            @endif
          @else
            <a href="{{ route('register') }}"
               class="block w-full py-[13px] rounded-[12px] text-center text-[13.5px] font-bold text-white no-underline transition-opacity duration-150 hover:opacity-90"
               style="background:{{ $brand }};">
              <i class="fa-solid fa-paper-plane mr-2 text-xs"></i>Daftar &amp; Lamar
            </a>
            <a href="{{ route('login') }}"
               class="mt-2 block w-full py-[11px] rounded-[12px] text-center text-[13px] font-semibold text-slate-600 no-underline border border-slate-200 hover:border-slate-300 transition-colors duration-150">
              Sudah punya akun? Masuk
            </a>
          @endauth

          <div class="mt-5 pt-4 border-t border-slate-100 flex flex-col gap-[10px]">
            <div class="flex items-center gap-3 text-[12.5px] text-slate-500">
              <i class="fa-solid fa-users w-4 text-center text-slate-400"></i>
              <span>Kuota <strong class="text-slate-700">{{ $jobOpening->quota }} orang</strong></span>
            </div>
            @if($jobOpening->max_applicants)
            <div class="flex items-center gap-3 text-[12.5px] text-slate-500">
              <i class="fa-solid fa-person-circle-check w-4 text-center text-slate-400"></i>
              <span>Maks. <strong class="text-slate-700">{{ number_format($jobOpening->max_applicants) }}</strong> pelamar</span>
            </div>
            @endif
            @if($jobOpening->applications_deadline)
            @php $deadlinePast = $jobOpening->applications_deadline->isPast(); @endphp
            <div class="flex items-center gap-3 text-[12.5px] {{ $deadlinePast ? 'text-red-500' : 'text-slate-500' }}">
              <i class="fa-solid fa-calendar-xmark w-4 text-center {{ $deadlinePast ? 'text-red-400' : 'text-slate-400' }}"></i>
              <span>
                @if($deadlinePast)
                  <strong class="text-red-600">Pendaftaran ditutup</strong>
                @else
                  Ditutup <strong class="text-slate-700">{{ $jobOpening->applications_deadline->translatedFormat('d M Y') }}</strong>
                @endif
              </span>
            </div>
            @endif
            <div class="flex items-center gap-3 text-[12.5px] text-slate-500">
              <i class="fa-solid fa-briefcase w-4 text-center text-slate-400"></i>
              <span>Full Time</span>
            </div>
            <div class="flex items-center gap-3 text-[12.5px] text-slate-500">
              <i class="fa-solid fa-circle-check w-4 text-center" style="color:{{ $brand }};"></i>
              <span>Lowongan aktif</span>
            </div>
          </div>
        </div>

        {{-- Disclaimer --}}
        <div class="sg-disclaimer rounded-[14px] px-5 py-4 bg-amber-50 border border-amber-200">
          <div class="sg-disclaimer-title text-[12px] font-bold text-amber-800 mb-1"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>Perhatian</div>
          <div class="sg-disclaimer-text text-[12px] text-amber-700 leading-[1.6]">Proses rekrutmen Syifa Global Group <strong>tidak memungut biaya</strong> dalam bentuk apapun.</div>
        </div>

      </div>
    </div>

  </main>
</div>

<script>
(function(){
  function syncIcons() {
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    document.querySelectorAll('.sg-icon-dark').forEach(function(i){ i.style.display = dark ? 'none' : ''; });
    document.querySelectorAll('.sg-icon-light').forEach(function(i){ i.style.display = dark ? '' : 'none'; });
  }
  window.sgToggleTheme = function() {
    var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('sg-theme', next);
    syncIcons();
  };
  syncIcons();
})();
</script>

</x-public-layout>
