<x-public-layout>
<x-slot name="title">Portal Dokter — Syifa Global Group</x-slot>

<div class="min-h-screen dokter-bg">
  <div class="h-1 bg-gradient-to-r from-teal-800 via-teal-600 to-teal-400" aria-hidden="true"></div>

  <header class="border-b border-teal-100 bg-white">
    <div class="max-w-[1180px] mx-auto px-4 sm:px-6 py-[14px] flex items-center gap-3 sm:gap-4">
      <img src="/logo-sgg.png" alt="Syifa Global Group" class="h-9 w-auto" width="36" height="36" />
      <div class="text-[11px] font-bold tracking-[0.06em] uppercase text-teal-800">Karir Dokter</div>
      <nav class="ml-auto flex items-center gap-2 sm:gap-3 text-sm font-semibold">
        @auth
          <a href="{{ route('dokter.riwayat') }}" class="text-slate-600 hover:text-teal-700">Riwayat</a>
          <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf
            <button class="px-4 py-2 rounded-xl border border-teal-200 text-teal-800 hover:bg-teal-50 font-bold">Keluar</button>
          </form>
        @else
          <a href="{{ route('dokter.masuk') }}" class="px-5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold no-underline">Masuk</a>
        @endauth
      </nav>
    </div>
  </header>

  <main class="max-w-[1180px] mx-auto px-6 pt-8 pb-[72px]">
    {{-- Banner: taruh foto di public/banner-dokter/banner-1.jpg, banner-2.jpg, dst. --}}
    <section x-data="{ i: 0, n: {{ max($banners->count(), 1) }} }" x-init="{{ $banners->count() > 1 ? 'setInterval(() => i = (i + 1) % n, 5000)' : '' }}"
             class="relative overflow-hidden rounded-[20px] text-white bg-gradient-to-br from-teal-500 to-teal-900">
      <div class="sg-hero-wrap md:!min-h-[260px] relative z-[2]">
        <div class="sg-hero md:!py-9 flex-1 min-w-0 flex flex-col justify-center">
          <div class="inline-flex items-center gap-2 px-[14px] py-[7px] rounded-full text-xs font-bold tracking-[0.05em] uppercase bg-white/[0.16] w-fit">
            <i class="fa-solid fa-user-doctor" aria-hidden="true"></i> Khusus tenaga dokter
          </div>
          <h1 class="mt-4 mb-3 text-3xl md:text-4xl font-extrabold leading-[1.1] tracking-tight">Bergabung bersama jaringan rumah sakit kami</h1>
          <p class="m-0 text-[15px] leading-[1.6] text-white/85">Dokter umum melamar ke rumah sakit yang sedang membuka penerimaan. Dokter spesialis dan subspesialis dapat mengajukan diri langsung ke rumah sakit tujuan.</p>
        </div>
        <div class="sg-hero-right md:!w-[34%] relative" style="min-height:220px" aria-hidden="true">
          @foreach ($banners as $k => $src)
            <img src="{{ $src }}" alt="" class="sg-hero-img absolute inset-0 w-full h-full transition-opacity duration-700"
                 :class="i === {{ $k }} ? 'opacity-100' : 'opacity-0'">
          @endforeach
        </div>
      </div>
    </section>

    @if ($application)
      <a href="{{ route('dokter.riwayat') }}" class="mt-10 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white border border-teal-300 p-6 no-underline hover:shadow-lg transition">
        <div>
          <div class="text-sm font-bold text-teal-800">Anda sudah mengirim lamaran</div>
          <div class="text-sm text-slate-500">Lihat riwayat dan lengkapi berkas yang belum terkumpul.</div>
        </div>
        <span class="text-sm font-bold text-teal-700">Lihat riwayat <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i></span>
      </a>
    @endif

    {{-- Dua jalur --}}
    <section class="mt-10 grid gap-5 md:grid-cols-2 {{ $application ? 'opacity-60 pointer-events-none' : '' }}" aria-label="Jalur lamaran dokter">
      <a href="{{ route('dokter.lamar', ['jenis' => 'umum']) }}"
         class="group block rounded-2xl bg-white border border-teal-100 p-7 no-underline hover:border-teal-400 hover:shadow-lg transition">
        <div class="w-12 h-12 rounded-xl bg-teal-600 text-white grid place-items-center text-xl mb-4"><i class="fa-solid fa-briefcase-medical" aria-hidden="true"></i></div>
        <h2 class="m-0 text-xl font-extrabold text-slate-800">Dokter Umum</h2>
        <p class="mt-2 mb-4 text-sm text-slate-500 leading-relaxed">Lamar ke rumah sakit yang sedang membuka penerimaan dokter umum. {{ $umumOpen }} rumah sakit dibuka saat ini.</p>
        <span class="text-sm font-bold text-teal-700 group-hover:underline">Lamar sekarang <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i></span>
      </a>
      <a href="{{ route('dokter.lamar', ['jenis' => 'spesialis']) }}"
         class="group block rounded-2xl bg-white border border-teal-100 p-7 no-underline hover:border-teal-400 hover:shadow-lg transition">
        <div class="w-12 h-12 rounded-xl bg-teal-800 text-white grid place-items-center text-xl mb-4"><i class="fa-solid fa-handshake" aria-hidden="true"></i></div>
        <h2 class="m-0 text-xl font-extrabold text-slate-800">Dokter Spesialis</h2>
        <p class="mt-2 mb-4 text-sm text-slate-500 leading-relaxed">Ajukan diri langsung ke rumah sakit tujuan tanpa menunggu lowongan. {{ $hospitals }} rumah sakit tersedia.</p>
        <span class="text-sm font-bold text-teal-700 group-hover:underline">Ajukan diri langsung <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i></span>
      </a>
    </section>
  </main>
</div>
</x-public-layout>
