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
<body class="m-0 bg-slate-100">

<div class="sg-rise min-h-screen">

  {{-- Top gradient bar --}}
  <div class="h-1" style="background:linear-gradient(90deg,#042c6c 0%,#0b47a1 55%,#B9308C 100%);"></div>

  {{-- Sticky navbar --}}
  <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur-[10px]">
    <div class="max-w-[1000px] mx-auto px-5 py-[14px] flex items-center gap-5">

      {{-- Logo --}}
      <a href="{{ route('welcome') }}" class="flex items-center gap-3 no-underline">
        <img src="/logo-sgg.png" alt="Syifa Global Group" class="h-8 w-auto" />
      </a>

      {{-- Desktop nav --}}
      <nav class="sg-desk-flex gap-1 ml-4 items-center">
        <a href="{{ route('dashboard') }}"
           class="px-[13px] py-[7px] rounded-[9px] text-[13.5px] font-semibold transition-colors duration-150 {{ request()->routeIs('dashboard') ? 'text-navy bg-navy/[0.07]' : 'text-slate-600 hover:text-slate-800' }}">
          <i class="fa-solid fa-gauge-high mr-1.5 text-xs"></i>Dashboard
        </a>
        <a href="{{ route('lamaran.index') }}"
           class="px-[13px] py-[7px] rounded-[9px] text-[13.5px] font-semibold {{ request()->routeIs('lamaran.*') ? 'text-navy font-bold' : 'text-slate-600 hover:text-slate-800' }} transition-colors duration-150">
          <i class="fa-solid fa-file-lines mr-1.5 text-xs"></i>Lamaran Saya
        </a>
      </nav>

      {{-- Desktop user dropdown --}}
      <div x-data="{ open: false }" class="relative sg-desk-flex ml-auto items-center">
        <button @click="open = !open"
                class="flex items-center gap-2 px-3 py-[7px] rounded-[11px] border border-slate-200 bg-white hover:border-slate-300 transition-colors duration-150 cursor-pointer outline-none">
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
             class="absolute right-0 w-52 rounded-[14px] border border-slate-200 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.1)] overflow-hidden z-50"
             style="top:calc(100% + 8px);">
          <div class="px-4 py-3 border-b border-slate-100">
            <div class="text-[12.5px] font-bold text-slate-800 truncate">{{ auth()->user()->name }}</div>
            <div class="text-[11.5px] text-slate-500 truncate">{{ auth()->user()->email }}</div>
          </div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full text-left px-4 py-[11px] text-[13px] font-semibold text-red-600 hover:bg-red-50 transition-colors duration-150 cursor-pointer border-0 bg-transparent outline-none">
              <i class="fa-solid fa-right-from-bracket mr-2"></i>Keluar
            </button>
          </form>
        </div>
      </div>

      {{-- Mobile: avatar + menu --}}
      <div x-data="{ open: false }" class="sg-mob ml-auto relative">
        <button @click="open = !open"
                class="w-[34px] h-[34px] rounded-full grid place-items-center text-[12px] font-extrabold text-white border-0 cursor-pointer outline-none"
                style="background:#042c6c;">
          {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
        </button>

        <div x-show="open" @click.outside="open = false"
             class="absolute right-0 w-52 rounded-[14px] border border-slate-200 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.1)] overflow-hidden z-50"
             style="top:calc(100% + 8px);">
          <div class="px-4 py-3 border-b border-slate-100">
            <div class="text-[12.5px] font-bold text-slate-800 truncate">{{ auth()->user()->name }}</div>
            <div class="text-[11.5px] text-slate-500 truncate">{{ auth()->user()->email }}</div>
          </div>
          <a href="{{ route('dashboard') }}"
             class="flex items-center gap-2 px-4 py-[11px] text-[13px] font-semibold text-slate-700 hover:bg-slate-50 no-underline transition-colors duration-150">
            <i class="fa-solid fa-gauge-high w-4 text-center text-slate-400"></i>Dashboard
          </a>
          <a href="{{ route('lamaran.index') }}"
             class="flex items-center gap-2 px-4 py-[11px] text-[13px] font-semibold text-slate-700 hover:bg-slate-50 no-underline transition-colors duration-150">
            <i class="fa-solid fa-file-lines w-4 text-center text-slate-400"></i>Lamaran Saya
          </a>
          <div class="border-t border-slate-100">
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit"
                      class="w-full text-left px-4 py-[11px] text-[13px] font-semibold text-red-600 hover:bg-red-50 transition-colors duration-150 cursor-pointer border-0 bg-transparent outline-none flex items-center gap-2">
                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>Keluar
              </button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </header>

  {{-- Main --}}
  <main class="max-w-[1000px] mx-auto px-5 pt-7 pb-[72px]">
    {{ $slot }}
  </main>

</div>

</body>
</html>
