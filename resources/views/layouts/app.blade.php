<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Rekrutmen — Syifa Global Group' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="m-0" x-data="{ sidebarOpen: false }">

<div class="sg-rise min-h-screen">

  {{-- Top gradient bar --}}
  <div class="h-1" style="background:linear-gradient(90deg,#042c6c 0%,#0b47a1 55%,#B9308C 100%);" aria-hidden="true"></div>

  {{-- Mobile sidebar --}}
  <div x-show="sidebarOpen" x-cloak class="sg-mob fixed inset-0 z-50" style="display:none;">
    <div class="absolute inset-0 bg-slate-900/40" @click="sidebarOpen = false"
         x-show="sidebarOpen"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
    <div class="absolute inset-y-0 left-0 w-[78%] max-w-[300px] bg-white shadow-[0_0_30px_rgba(15,23,42,0.15)] flex flex-col"
         x-show="sidebarOpen"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
      <div class="flex items-center justify-between px-5 py-[18px] border-b border-slate-100">
        <img src="/logo-sgg.png" alt="Syifa Global Group" class="h-7 w-auto" />
        <button @click="sidebarOpen = false" class="w-8 h-8 grid place-items-center rounded-lg border-0 bg-transparent cursor-pointer text-slate-400">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      <nav class="flex-1 px-3 py-4 flex flex-col gap-1" aria-label="Navigasi">
        <a href="{{ route('dashboard') }}" @click="sidebarOpen = false"
           class="px-4 py-[11px] rounded-[10px] text-[14px] font-semibold no-underline flex items-center gap-3 {{ request()->routeIs('dashboard') ? 'sg-nav-active' : 'sg-nav-inactive' }}">
          <i class="fa-solid fa-gauge-high w-4 text-center text-xs"></i>Dashboard
        </a>
        <a href="{{ route('lamaran.index') }}" @click="sidebarOpen = false"
           class="px-4 py-[11px] rounded-[10px] text-[14px] font-semibold no-underline flex items-center gap-3 {{ request()->routeIs('lamaran.*') ? 'sg-nav-active' : 'sg-nav-inactive' }}">
          <i class="fa-solid fa-file-lines w-4 text-center text-xs"></i>Lamaran Saya
        </a>
        @php($navApp = auth()->user()->latestApplication)
        @if($navApp?->status === 'shortlisted' && $navApp?->quiz_sent_at)
        <a href="{{ route('tes-online.show') }}" @click="sidebarOpen = false"
           class="px-4 py-[11px] rounded-[10px] text-[14px] font-semibold no-underline flex items-center gap-3 {{ request()->routeIs('tes-online.*') ? 'sg-nav-active' : 'sg-nav-inactive' }}">
          <i class="fa-solid fa-computer w-4 text-center text-xs"></i>Tes
        </a>
        @endif
        @if(auth()->user()->candidate)
        {{-- Perusahaan tanpa persetujuan onboarding (companies.requires_agreement = false): menu disembunyikan. --}}
        @if(auth()->user()->candidate->jobApplication?->jobOpening?->company?->requires_agreement ?? false)
        <a href="{{ route('persetujuan.edit') }}" @click="sidebarOpen = false"
           class="px-4 py-[11px] rounded-[10px] text-[14px] font-semibold no-underline flex items-center gap-3 {{ request()->routeIs('persetujuan.*') ? 'sg-nav-active' : 'sg-nav-inactive' }}">
          <i class="fa-solid fa-file-signature w-4 text-center text-xs"></i>Persetujuan
        </a>
        @endif
        <a href="{{ route('data-diri.edit') }}" @click="sidebarOpen = false"
           class="px-4 py-[11px] rounded-[10px] text-[14px] font-semibold no-underline flex items-center gap-3 {{ request()->routeIs('data-diri.*') ? 'sg-nav-active' : 'sg-nav-inactive' }}">
          <i class="fa-solid fa-id-card w-4 text-center text-xs"></i>Data Diri Kandidat
        </a>
        @endif
      </nav>
      <div class="px-3 py-4 border-t border-slate-100">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="w-full text-left px-4 py-[11px] rounded-[10px] text-[14px] font-semibold text-red-500 transition-colors duration-150 cursor-pointer border-0 bg-transparent outline-none flex items-center gap-3">
            <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>Keluar
          </button>
        </form>
      </div>
    </div>
  </div>

  {{-- Navbar --}}
  <header class="sg-app-navbar sticky top-0 z-40 border-b border-slate-200 backdrop-blur-[10px]">
    <div class="max-w-[1000px] mx-auto px-5 py-[14px] flex items-center gap-5">

      {{-- Mobile: hamburger --}}
      <button @click="sidebarOpen = true" class="sg-mob w-9 h-9 -ml-1 rounded-lg border-0 bg-transparent cursor-pointer text-slate-600" style="display:flex;align-items:center;justify-content:center;" aria-label="Buka navigasi">
        <i class="fa-solid fa-bars text-lg"></i>
      </button>

      {{-- Logo --}}
      <a href="{{ route('welcome') }}" class="flex items-center gap-3 no-underline">
        <img src="/logo-sgg.png" alt="Syifa Global Group" class="h-8 w-auto" />
      </a>

      {{-- Desktop nav --}}
      <nav class="sg-desk-flex gap-1 ml-4 items-center" aria-label="Navigasi">
        <a href="{{ route('dashboard') }}"
           class="px-[13px] py-[7px] rounded-[9px] text-[13.5px] font-semibold transition-colors duration-150 no-underline {{ request()->routeIs('dashboard') ? 'sg-nav-active' : 'sg-nav-inactive' }}">
          <i class="fa-solid fa-gauge-high mr-1.5 text-xs"></i>Dashboard
        </a>
        <a href="{{ route('lamaran.index') }}"
           class="px-[13px] py-[7px] rounded-[9px] text-[13.5px] font-semibold transition-colors duration-150 no-underline {{ request()->routeIs('lamaran.*') ? 'sg-nav-active' : 'sg-nav-inactive' }}">
          <i class="fa-solid fa-file-lines mr-1.5 text-xs"></i>Lamaran Saya
        </a>
        @php($navApp = auth()->user()->latestApplication)
        @if($navApp?->status === 'shortlisted' && $navApp?->quiz_sent_at)
        <a href="{{ route('tes-online.show') }}"
           class="px-[13px] py-[7px] rounded-[9px] text-[13.5px] font-semibold transition-colors duration-150 no-underline {{ request()->routeIs('tes-online.*') ? 'sg-nav-active' : 'sg-nav-inactive' }}">
          <i class="fa-solid fa-computer mr-1.5 text-xs"></i>Tes
        </a>
        @endif
        @if(auth()->user()->candidate)
        @if(auth()->user()->candidate->jobApplication?->jobOpening?->company?->requires_agreement ?? false)
        <a href="{{ route('persetujuan.edit') }}"
           class="px-[13px] py-[7px] rounded-[9px] text-[13.5px] font-semibold transition-colors duration-150 no-underline {{ request()->routeIs('persetujuan.*') ? 'sg-nav-active' : 'sg-nav-inactive' }}">
          <i class="fa-solid fa-file-signature mr-1.5 text-xs"></i>Persetujuan
        </a>
        @endif
        <a href="{{ route('data-diri.edit') }}"
           class="px-[13px] py-[7px] rounded-[9px] text-[13.5px] font-semibold transition-colors duration-150 no-underline {{ request()->routeIs('data-diri.*') ? 'sg-nav-active' : 'sg-nav-inactive' }}">
          <i class="fa-solid fa-id-card mr-1.5 text-xs"></i>Data Diri Kandidat
        </a>
        @endif
      </nav>

      {{-- Desktop: user dropdown + dark toggle --}}
      <div class="sg-desk-flex ml-auto items-center gap-2">
        <div x-data="{ open: false }" class="relative">
          <button @click="open = !open"
                  class="sg-app-user-btn flex items-center gap-2 px-3 py-[7px] rounded-[11px] border border-slate-200 bg-white hover:border-slate-300 transition-colors duration-150 cursor-pointer outline-none">
            <div class="w-[26px] h-[26px] rounded-full grid place-items-center text-[11.5px] font-extrabold text-white shrink-0"
                 style="background:#042c6c;">
              {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </div>
            <span class="text-[13px] font-semibold text-slate-700 max-w-[130px] truncate">{{ auth()->user()->name }}</span>
            <i class="fa-solid fa-chevron-down text-[9px] text-slate-400"
               :style="open ? 'transform:rotate(180deg)' : ''"
               style="transition:transform .15s"></i>
          </button>

          <div x-show="open" @click.outside="open = false"
               x-transition:enter="transition ease-out duration-100"
               x-transition:enter-start="opacity-0 translate-y-[-4px]"
               x-transition:enter-end="opacity-100 translate-y-0"
               x-transition:leave="transition ease-in duration-75"
               x-transition:leave-start="opacity-100 translate-y-0"
               x-transition:leave-end="opacity-0 translate-y-[-4px]"
               class="sg-app-dropdown absolute right-0 w-52 rounded-[14px] border border-slate-200 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.1)] overflow-hidden z-50"
               style="top:calc(100% + 8px);">
            <div class="sg-dd-header px-4 py-3 border-b border-slate-100">
              <div class="text-[12.5px] font-bold text-slate-800 truncate">{{ auth()->user()->name }}</div>
              <div class="text-[11.5px] text-slate-500 truncate">{{ auth()->user()->email }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit"
                      class="sg-dd-logout w-full text-left px-4 py-[11px] text-[13px] font-semibold text-red-500 transition-colors duration-150 cursor-pointer border-0 bg-transparent outline-none">
                <i class="fa-solid fa-right-from-bracket mr-2"></i>Keluar
              </button>
            </form>
          </div>
        </div>
      </div>

      {{-- Mobile: dark toggle + avatar menu --}}
      <div class="sg-mob ml-auto" style="display:flex;align-items:center;gap:8px;">
        <div x-data="{ open: false }" class="relative">
          <button @click="open = !open"
                  class="w-[34px] h-[34px] rounded-full grid place-items-center text-[12px] font-extrabold text-white border-0 cursor-pointer outline-none"
                  style="background:#042c6c;">
            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
          </button>

          <div x-show="open" @click.outside="open = false"
               class="sg-app-dropdown absolute right-0 w-52 rounded-[14px] border border-slate-200 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.1)] overflow-hidden z-50"
               style="top:calc(100% + 8px);">
            <div class="sg-dd-header px-4 py-3 border-b border-slate-100">
              <div class="text-[12.5px] font-bold text-slate-800 truncate">{{ auth()->user()->name }}</div>
              <div class="text-[11.5px] text-slate-500 truncate">{{ auth()->user()->email }}</div>
            </div>
            <div class="sg-dd-border border-t border-slate-100">
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="sg-dd-logout w-full text-left px-4 py-[11px] text-[13px] font-semibold text-red-500 transition-colors duration-150 cursor-pointer border-0 bg-transparent outline-none flex items-center gap-2">
                  <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>Keluar
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

    </div>
  </header>

  {{-- Main --}}
  <main class="lg:w-11/12 mx-auto px-5 pt-7 pb-[72px]">
    {{ $slot }}
  </main>

</div>

</body>
</html>
