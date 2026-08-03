<x-public-layout>
<x-slot name="title">{{ $jobOpening->title }} — Syifa Global Group</x-slot>

@php
function sgDetailIcon(string $title): string {
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

<div class="sg-rise min-h-screen">

  {{-- Top gradient bar --}}
  <div class="h-1" style="background:linear-gradient(90deg,#042c6c 0%,#0b47a1 55%,#B9308C 100%);"></div>

  {{-- Sticky navbar --}}
  <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur-[10px]">
    <div class="max-w-[1180px] mx-auto px-6 py-[14px] flex items-center gap-7">
      <div class="flex items-center gap-3">
        <img src="/logo-sgg.png" alt="Syifa Global Group" class="h-9 w-auto" />
        <div class="text-[11px] font-semibold text-slate-500 tracking-[0.06em] uppercase sg-desk">Karier &amp; Rekrutmen</div>
      </div>
      <nav class="sg-desk-flex gap-1.5 ml-auto items-center">
        <a href="{{ route('welcome') }}" class="px-[14px] py-2 rounded-[10px] text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors duration-150">Beranda</a>
        <a href="{{ route('welcome') }}#lowongan" class="px-[14px] py-2 rounded-[10px] text-sm font-semibold text-navy bg-navy/[0.07]">Lowongan</a>
        @auth
          <a href="{{ route('dashboard') }}" class="ml-[10px] px-5 py-[10px] rounded-[11px] bg-navy hover:bg-navy-dark text-white text-sm font-bold no-underline transition-colors duration-150">Dashboard</a>
        @else
          <a href="{{ route('login') }}" class="px-[14px] py-2 rounded-[10px] text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors duration-150">Masuk</a>
          <a href="{{ route('register') }}" class="ml-1 px-5 py-[10px] rounded-[11px] bg-navy hover:bg-navy-dark text-white text-sm font-bold no-underline transition-colors duration-150">Lamar Sekarang</a>
        @endauth
      </nav>
      <div class="sg-mob ml-auto">
        @auth
          <a href="{{ route('dashboard') }}" class="px-4 py-[9px] rounded-[11px] bg-navy text-white text-[13.5px] font-bold no-underline whitespace-nowrap">Dashboard</a>
        @else
          <a href="{{ route('login') }}" class="px-4 py-[9px] rounded-[11px] bg-navy text-white text-[13.5px] font-bold no-underline whitespace-nowrap">Masuk</a>
        @endauth
      </div>
    </div>
  </header>

  {{-- Main --}}
  <main class="max-w-[1180px] mx-auto px-6 pt-8 pb-[72px]">

    {{-- Back --}}
    <a href="{{ route('welcome') }}#lowongan"
       class="inline-flex items-center gap-2 mb-6 text-[13px] font-semibold text-slate-500 no-underline hover:text-navy transition-colors duration-150">
      <i class="fa-solid fa-arrow-left text-[11px]"></i>Semua Lowongan
    </a>

    {{-- Hero card --}}
    <div class="relative overflow-hidden rounded-[20px] text-white px-8 py-8"
         style="background:linear-gradient(135deg,#042c6c 0%,#0b47a1 100%);">
      <div class="absolute w-[280px] h-[280px] rounded-full -right-[80px] -top-[130px] pointer-events-none bg-white/[0.05]"></div>
      <div class="absolute w-[140px] h-[140px] rounded-[34px] rotate-[22deg] right-[200px] -bottom-[80px] pointer-events-none bg-magenta/[0.16]"></div>
      <div class="relative z-[1] flex flex-wrap gap-5 items-center">
        <div class="w-[60px] h-[60px] shrink-0 rounded-[18px] grid place-items-center text-[24px] bg-white/[0.14]">
          <i class="fa-solid {{ sgDetailIcon($jobOpening->title) }}"></i>
        </div>
        <div class="flex-1 min-w-0">
          <div class="text-[11.5px] font-bold tracking-[0.08em] uppercase text-white/70 mb-1">Lowongan Terbuka</div>
          <h1 class="m-0 text-[26px] font-extrabold tracking-[-0.6px] leading-[1.15]">{{ $jobOpening->title }}</h1>
          <div class="mt-2 text-[14px] text-white/80 font-semibold">{{ $jobOpening->company_name }}</div>
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
        <div class="bg-white border border-slate-200 rounded-[18px] px-7 py-6" style="border-top: 3px solid #042c6c;">
          <h2 class="m-0 mb-4 text-[16px] font-extrabold text-slate-800 tracking-[-0.3px]">
            <i class="fa-solid fa-file-lines mr-2 text-navy text-[14px]"></i>Deskripsi Pekerjaan
          </h2>
          <div class="text-[14px] leading-[1.75] text-slate-600 whitespace-pre-line">{{ $jobOpening->description }}</div>
        </div>
        @endif

        @if($jobOpening->requirements)
        <div class="bg-white border border-slate-200 rounded-[18px] px-7 py-6" style="border-top: 3px solid #047C54;">
          <h2 class="m-0 mb-4 text-[16px] font-extrabold text-slate-800 tracking-[-0.3px]">
            <i class="fa-solid fa-list-check mr-2 text-[14px]" style="color:#047C54;"></i>Kualifikasi &amp; Persyaratan
          </h2>
          <div class="text-[14px] leading-[1.75] text-slate-600 whitespace-pre-line">{{ $jobOpening->requirements }}</div>
        </div>
        @endif

        {{-- Proses rekrutmen singkat --}}
        <div class="bg-white border border-slate-200 rounded-[18px] px-7 py-6" style="border-top: 3px solid #B9308C;">
          <h2 class="m-0 mb-5 text-[16px] font-extrabold text-slate-800 tracking-[-0.3px]">
            <i class="fa-solid fa-route mr-2 text-[13px]" style="color:#B9308C;"></i>Alur Seleksi
          </h2>
          <div class="flex flex-col gap-3">
            @foreach([
              ['icon' => 'fa-paper-plane', 'label' => 'Kirim Lamaran',   'desc' => 'Isi formulir dan unggah CV'],
              ['icon' => 'fa-magnifying-glass','label' => 'Seleksi Berkas','desc' => 'Tim HR meninjau dokumen'],
              ['icon' => 'fa-comments',    'label' => 'Wawancara',        'desc' => 'Jadwal dikirim via WhatsApp'],
              ['icon' => 'fa-circle-check','label' => 'Keputusan',        'desc' => 'Offering letter & onboarding'],
            ] as $s)
            <div class="flex items-center gap-4">
              <div class="w-9 h-9 shrink-0 rounded-[11px] grid place-items-center text-[13px]" style="background:rgba(4,44,108,0.08);color:#042c6c;">
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
        <div class="bg-white border border-slate-200 rounded-[18px] px-6 py-6 sticky top-[74px]">
          <div class="text-[12px] font-bold uppercase tracking-[0.06em] text-slate-500 mb-3">Lamar Sekarang</div>
          <div class="text-[15px] font-extrabold text-slate-800 leading-[1.3] mb-1">{{ $jobOpening->title }}</div>
          <div class="text-[13px] text-slate-500 mb-5">{{ $jobOpening->company_name }}</div>

          @auth
            @php $hasActive = \App\Models\JobApplication::hasActiveApplication(auth()->id()); @endphp
            @if($hasActive)
              <div class="w-full py-[13px] rounded-[12px] text-center text-[13.5px] font-bold text-slate-400 bg-slate-100 cursor-not-allowed">
                Kamu sudah memiliki lamaran aktif
              </div>
              <a href="{{ route('dashboard') }}" class="mt-3 block text-center text-[12.5px] font-semibold text-navy no-underline hover:underline">
                Pantau status lamaran →
              </a>
            @else
              <a href="{{ route('lamaran.create', $jobOpening) }}"
                 class="block w-full py-[13px] rounded-[12px] text-center text-[13.5px] font-bold text-white no-underline transition-colors duration-150"
                 style="background:#047C54;" onmouseover="this.style.background='#036444'" onmouseout="this.style.background='#047C54'">
                <i class="fa-solid fa-paper-plane mr-2 text-xs"></i>Kirim Lamaran
              </a>
            @endif
          @else
            <a href="{{ route('register') }}"
               class="block w-full py-[13px] rounded-[12px] text-center text-[13.5px] font-bold text-white no-underline transition-colors duration-150"
               style="background:#047C54;" onmouseover="this.style.background='#036444'" onmouseout="this.style.background='#047C54'">
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
              <i class="fa-solid fa-circle-check w-4 text-center text-sgg-green"></i>
              <span>Lowongan aktif</span>
            </div>
          </div>
        </div>

        {{-- Disclaimer --}}
        <div class="rounded-[14px] px-5 py-4 bg-amber-50 border border-amber-200">
          <div class="text-[12px] font-bold text-amber-800 mb-1"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>Perhatian</div>
          <div class="text-[12px] text-amber-700 leading-[1.6]">Proses rekrutmen Syifa Global Group <strong>tidak memungut biaya</strong> dalam bentuk apapun.</div>
        </div>

      </div>
    </div>

  </main>
</div>

</x-public-layout>
