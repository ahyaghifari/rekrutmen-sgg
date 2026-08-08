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
    <div class="relative overflow-hidden rounded-[20px] text-white px-8 py-8"
         style="background:linear-gradient(135deg,{{ $brand }} 0%,{{ $brandDark }} 100%);">
      <div class="absolute w-[280px] h-[280px] rounded-full -right-[80px] -top-[130px] pointer-events-none bg-white/[0.05]"></div>
      <div class="absolute w-[140px] h-[140px] rounded-[34px] rotate-[22deg] right-[200px] -bottom-[80px] pointer-events-none bg-white/[0.08]"></div>
      <div class="relative z-[1] flex flex-wrap gap-5 items-center">
        <div class="w-[64px] h-[64px] shrink-0 rounded-[18px] grid place-items-center overflow-hidden bg-white/[0.18]">
          @if($logo)
            <img src="{{ $logo }}" alt="{{ $co->name }}" class="w-full h-full object-contain p-[10px]">
          @else
            <i class="fa-solid {{ sgJobIcon($jobOpening->title) }} text-[24px]"></i>
          @endif
        </div>
        <div class="flex-1 min-w-0">
          <div class="text-[11.5px] font-bold tracking-[0.08em] uppercase text-white/70 mb-1">
            {{ $co?->name ?? $jobOpening->company_name }}
          </div>
          <h1 class="m-0 text-[26px] font-extrabold tracking-[-0.6px] leading-[1.15]">{{ $jobOpening->title }}</h1>
        </div>
        <div class="shrink-0 flex flex-wrap gap-3 items-center">
          <div class="flex items-center gap-[10px] px-4 py-[10px] rounded-[12px] bg-white/[0.12] text-[13px] font-semibold">
            <i class="fa-solid fa-users text-white/70 text-xs"></i>{{ $jobOpening->quota }} posisi
          </div>
          <div class="flex items-center gap-[10px] px-4 py-[10px] rounded-[12px] bg-white/[0.12] text-[13px] font-semibold">
            <i class="fa-solid fa-clock text-white/70 text-xs"></i>Full Time
          </div>
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
