@props(['title', 'subtitle'])
<x-public-layout>
<x-slot name="title">{{ $title }} — Portal Dokter</x-slot>
<div class="min-h-screen bg-teal-50/40">
  <div class="h-1 bg-gradient-to-r from-teal-800 via-teal-600 to-teal-400" aria-hidden="true"></div>
  <div class="max-w-[480px] mx-auto px-6 py-10">
    <a href="{{ route('dokter.index') }}" class="inline-flex items-center gap-2 mb-6 px-[14px] py-2 rounded-[10px] border border-teal-100 bg-white text-slate-600 text-[13px] font-bold no-underline hover:border-teal-300">
      <i class="fa-solid fa-arrow-left text-[11px]" aria-hidden="true"></i> Portal dokter
    </a>
    <div class="flex items-center gap-3 mb-5">
      <img src="/logo-sgg.png" alt="Syifa Global Group" class="h-9 w-auto" width="36" height="36" />
      <div class="text-[11px] font-bold tracking-[0.06em] uppercase text-teal-800">Karir Dokter</div>
    </div>
    <h1 class="m-0 mb-1.5 text-[26px] font-extrabold text-slate-800 tracking-[-0.7px]">{{ $title }}</h1>
    <p class="m-0 mb-7 text-sm text-slate-500">{{ $subtitle }}</p>
    <div class="rounded-[20px] bg-white border border-teal-100 p-7 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
      {{ $slot }}
    </div>
  </div>
</div>
</x-public-layout>
