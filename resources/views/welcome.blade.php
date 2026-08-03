<x-public-layout>
<x-slot name="title">Portal Rekrutmen — Syifa Global Group</x-slot>

@php
function sgJobIcon(string $title): string {
    $t = strtolower($title);
    if (str_contains($t, 'perawat') || str_contains($t, 'nurse')) return 'fa-user-nurse';
    if (str_contains($t, 'bidan'))                                  return 'fa-baby';
    if (str_contains($t, 'apoteker') || str_contains($t, 'farmasi')) return 'fa-prescription-bottle-medical';
    if (str_contains($t, 'admin') || str_contains($t, 'rekam'))     return 'fa-folder';
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
        <div class="text-[11px] font-semibold text-slate-500 tracking-[0.06em] uppercase">Karier &amp; Rekrutmen</div>
      </div>
      <nav class="sg-desk-flex gap-1.5 ml-auto items-center">
        <a href="{{ route('welcome') }}" class="px-[14px] py-2 rounded-[10px] text-sm font-semibold text-navy bg-navy/[0.07]">Beranda</a>
        <a href="#lowongan" class="px-[14px] py-2 rounded-[10px] text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors duration-150">Lowongan</a>
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

  {{-- Main content --}}
  <main class="max-w-[1180px] mx-auto px-6 pt-8 pb-[72px]">

    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-[20px] text-white" style="background:linear-gradient(135deg,#042c6c 0%,#0b47a1 100%);">
      <div class="absolute w-[200px] h-[200px] rounded-[34px] rotate-[24deg] -left-[70px] -bottom-[70px] pointer-events-none bg-white/[0.05]"></div>
      <div class="sg-hero-wrap relative z-[1]">
        {{-- Left: sg-hero supplies responsive padding --}}
        <div class="sg-hero flex-1 min-w-0 flex flex-col justify-center">
          <div class="inline-flex items-center gap-2 px-[14px] py-[7px] rounded-full text-xs font-bold tracking-[0.05em] uppercase bg-white/[0.14] w-fit">
            <i class="fa-solid fa-briefcase"></i>
            {{ $openings->count() }} posisi dibuka saat ini
          </div>
          <h1
            x-data="{
              lines: ['Join and Grow', 'With Us'],
              out: ['',''],
              init() {
                const full = this.lines.join('\n');
                let i = 0, li = 0;
                const tick = () => {
                  if (li >= this.lines.length) return;
                  const line = this.lines[li];
                  this.out[li] = line.slice(0, ++i);
                  if (i >= line.length) { li++; i = 0; }
                  if (li < this.lines.length) setTimeout(tick, 68);
                };
                setTimeout(tick, 300);
              }
            }"
            class="sg-h1 mt-5 mb-[14px] leading-[1.1] font-extrabold tracking-[-1.2px]"
          >
            <span x-text="out[0]"></span><span x-show="out[0].length < 13" class="sg-cursor">|</span><br>
            <span x-text="out[1]"></span><span x-show="out[0].length >= 13" class="sg-cursor">|</span>
          </h1>
          <div class="text-[15px] font-bold tracking-[0.18em] uppercase text-white/70 mb-[14px]">Syifa Global Group</div>

          <a href="#lowongan" class="mt-3 inline-flex px-[26px] py-[14px] rounded-[11px] bg-white text-navy text-sm font-bold no-underline hover:bg-slate-100 transition-colors duration-150">Lihat Lowongan <i class="fa-solid fa-arrow-down ml-2 text-xs self-center"></i></a>
        </div>
        {{-- Right: no padding, image fills full column --}}
        <div class="sg-hero-right">
          <img src="/three-person.png" alt="" class="sg-hero-img" />
        </div>
      </div>
    </section>

    {{-- Stats --}}
    <section class="sg-stats sg-fade mt-12 bg-white border border-slate-200 rounded-[18px] overflow-hidden">
      <div class="py-[22px] px-[26px]">
        <div class="text-xs font-bold tracking-[0.07em] uppercase text-slate-500">Lowongan Aktif</div>
        <div class="mt-1.5 text-[30px] font-extrabold text-navy tracking-[-1px]" data-count="{{ $openings->count() }}" data-suffix="">{{ $openings->count() }}</div>
      </div>
      <div class="py-[22px] px-[26px]">
        <div class="text-xs font-bold tracking-[0.07em] uppercase text-slate-500">Unit Kerja</div>
        <div class="mt-1.5 text-[30px] font-extrabold text-navy tracking-[-1px]" data-count="{{ $openings->pluck('company_name')->unique()->count() }}" data-suffix="">{{ $openings->pluck('company_name')->unique()->count() }}</div>
      </div>
      <div class="py-[22px] px-[26px]">
        <div class="text-xs font-bold tracking-[0.07em] uppercase text-slate-500">Kandidat Bergabung</div>
        <div class="mt-1.5 text-[30px] font-extrabold text-sgg-green tracking-[-1px]" data-count="1200" data-suffix="+" data-sep=".">1.200+</div>
      </div>
      <div class="py-[22px] px-[26px]">
        <div class="text-xs font-bold tracking-[0.07em] uppercase text-slate-500">Rata-rata Respons</div>
        <div class="mt-1.5 text-[30px] font-extrabold text-navy tracking-[-1px]" data-count="3" data-suffix=" hari">3 hari</div>
      </div>
    </section>

    {{-- Lowongan Terbuka --}}
    <section id="lowongan" class="mt-16">
      <div class="sg-fade flex items-baseline justify-between mb-5">
        <h2 class="m-0 text-xl font-extrabold text-slate-800 tracking-[-0.4px]">Lowongan Terbuka</h2>
        <span class="text-[13.5px] font-semibold text-slate-500">{{ $openings->count() }} posisi tersedia</span>
      </div>
      <div class="sg-job-grid">
        @foreach($openings as $opening)
        <article class="sg-job-card sg-fade" style="transition-delay: {{ $loop->index * 0.07 }}s;">
          <div class="flex gap-[14px] items-start">
            <div class="w-[46px] h-[46px] shrink-0 rounded-[14px] grid place-items-center text-[17px] text-navy bg-navy/[0.08]">
              <i class="fa-solid {{ sgJobIcon($opening->title) }}"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap gap-1.5 items-center">
                <h3 class="m-0 text-[15.5px] font-bold text-slate-800 leading-[1.3]">{{ $opening->title }}</h3>
                <span class="px-[9px] py-[3px] rounded-full text-[11px] font-bold text-sgg-green whitespace-nowrap bg-sgg-green/[0.1]">Full Time</span>
              </div>
              <div class="mt-1 text-[13px] font-semibold text-slate-600">{{ $opening->company_name }}</div>
            </div>
          </div>
          <div class="mt-3 text-[13px] text-slate-500 leading-normal line-clamp-2">{{ $opening->description }}</div>
          <div class="sg-job-card-footer">
            <span class="text-[12.5px] text-slate-500"><i class="fa-solid fa-users text-slate-400 mr-[5px]"></i>Kuota: {{ $opening->quota }} orang</span>
            <div style="display:flex;gap:8px;">
              <a href="{{ route('lowongan.show', $opening) }}" class="sg-job-card-btn" style="background:#f1f5f9;color:#042c6c;">Detail</a>
              <a href="{{ route('lamaran.create', $opening) }}" class="sg-job-card-btn">Lamar</a>
            </div>
          </div>
        </article>
        @endforeach
      </div>
    </section>

    {{-- Timeline --}}
    <section class="mt-32">
      <div class="sg-fade text-center max-w-[620px] mx-auto mb-[34px]">
        <div class="text-[11.5px] font-bold tracking-[0.1em] uppercase text-slate-500">Alur Rekrutmen</div>
        <h2 class="mt-2.5 mb-2 text-[26px] font-extrabold text-slate-800 tracking-[-0.6px]">Lima tahap, semuanya transparan</h2>
        <p class="m-0 text-[14.5px] leading-[1.6] text-slate-500">Setiap perpindahan tahap dapat dipantau kapan saja dari dashboard kandidat.</p>
      </div>
      <div class="sg-tl">
        @foreach([
          ['icon' => 'fa-plus',         'bg' => 'bg-navy',      'border' => '',                    'color' => 'text-white', 'label' => 'Daftar &amp; Lamar',  'desc' => 'Buat akun kandidat dan isi formulir lamaran',   'last' => false],
          ['icon' => 'fa-circle-check', 'bg' => 'bg-white',     'border' => 'border-2 border-navy','color' => 'text-navy',  'label' => 'Seleksi Berkas',       'desc' => 'Tim HR meninjau kelengkapan dokumen',            'last' => false, 'regular' => true],
          ['icon' => 'fa-user-group',   'bg' => 'bg-white',     'border' => 'border-2 border-navy','color' => 'text-navy',  'label' => 'Wawancara',            'desc' => 'Jadwal dikirim via email &amp; WhatsApp',       'last' => false],
          ['icon' => 'fa-file-lines',   'bg' => 'bg-white',     'border' => 'border-2 border-navy','color' => 'text-navy',  'label' => 'Surat Penawaran',      'desc' => 'Offering letter dan negosiasi',                  'last' => false, 'regular' => true],
          ['icon' => 'fa-circle-check', 'bg' => 'bg-sgg-green', 'border' => '',                    'color' => 'text-white', 'label' => 'Bergabung',            'desc' => 'Onboarding dan mulai berkarier',                 'last' => true],
        ] as $i => $step)
        <div class="sg-fade relative text-center" style="transition-delay: {{ $i * 0.1 }}s;">
          @if(!$step['last'])
          <div class="sg-tl-line absolute top-[33px] border-t-2 border-dashed border-slate-300" style="left:calc(50% + 40px);right:calc(-50% + 40px);"></div>
          @endif
          <div class="relative z-[1] w-[66px] h-[66px] mx-auto rounded-full grid place-items-center text-[19px] {{ $step['bg'] }} {{ $step['border'] }} {{ $step['color'] }}">
            <i class="{{ isset($step['regular']) ? 'fa-regular' : 'fa-solid' }} {{ $step['icon'] }}"></i>
          </div>
          <div class="mt-4 text-[15px] font-extrabold {{ $step['last'] ? 'text-sgg-green' : 'text-slate-800' }}">{!! $step['label'] !!}</div>
          <div class="mt-[5px] text-[13px] leading-[1.5] text-slate-500">{!! $step['desc'] !!}</div>
        </div>
        @endforeach
      </div>
    </section>

    {{-- Butuh bantuan --}}
    <section class="sg-fade mt-24 relative overflow-hidden rounded-[20px] text-white flex flex-wrap gap-7 items-center justify-between" style="background:linear-gradient(135deg,#047C54 0%,#024732 100%);padding:clamp(28px,5vw,40px) clamp(24px,5vw,44px);">
      <div class="absolute w-[280px] h-[280px] rounded-full -right-[60px] -top-[140px] pointer-events-none bg-white/[0.06]"></div>
      <div class="absolute w-[160px] h-[160px] rounded-full -left-[60px] -bottom-[80px] pointer-events-none bg-white/[0.04]"></div>
      <div class="relative z-[1]">
        <div class="text-[11.5px] font-bold tracking-[0.1em] uppercase text-white/70 mb-[10px]">Hubungi Tim HR</div>
        <h2 class="m-0 mb-[10px] text-[26px] font-extrabold tracking-[-0.6px]">Butuh bantuan?</h2>
        <p class="m-0 text-[15px] leading-[1.6] text-white/[0.82] max-w-[480px]">Tim rekrutmen kami siap menjawab pertanyaan seputar lowongan dan status lamaran pada hari kerja, 08.00–16.00 WIB.</p>
      </div>
      <div class="relative z-[1] flex flex-col gap-3">
        <a href="mailto:rekrutmen@syifaglobal.co.id" class="flex items-center gap-3 px-5 py-[14px] rounded-[14px] no-underline text-white bg-white/[0.14] hover:bg-white/[0.22] transition-colors duration-150">
          <div class="w-9 h-9 shrink-0 rounded-[10px] grid place-items-center text-sm bg-white/[0.18]"><i class="fa-regular fa-envelope"></i></div>
          <span class="text-sm font-bold">rekrutmen@syifaglobal.co.id</span>
        </a>
        <a href="https://wa.me/628121234567" target="_blank" class="flex items-center gap-3 px-5 py-[14px] rounded-[14px] no-underline text-white bg-white/[0.14] hover:bg-white/[0.22] transition-colors duration-150">
          <div class="w-9 h-9 shrink-0 rounded-[10px] grid place-items-center text-sm bg-white/[0.18]"><i class="fa-brands fa-whatsapp"></i></div>
          <span class="text-sm font-bold">0812-1234-5678</span>
        </a>
      </div>
    </section>

  </main>

  {{-- Footer --}}
  <footer class="bg-white border-t border-slate-200 px-6 pt-[34px] pb-16">
    <div class="max-w-[1180px] mx-auto flex flex-wrap gap-5 items-center justify-between">
      <div class="text-[13px] text-slate-500">&copy; {{ date('Y') }} Syifa Global Group &middot; Seluruh proses rekrutmen tidak dipungut biaya apa pun.</div>
      <div class="flex gap-5 text-[13px] font-semibold">
        <a href="#">Kebijakan Privasi</a>
        <a href="#">Syarat &amp; Ketentuan</a>
        <a href="#">Hubungi HR</a>
      </div>
    </div>
  </footer>

</div>

<script>
(function () {
  // ── Scroll fade-in ──────────────────────────────────────────
  const fadeObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        fadeObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.08 });

  document.querySelectorAll('.sg-fade').forEach(el => fadeObserver.observe(el));

  // ── Counter animation ────────────────────────────────────────
  function formatNum(n, sep) {
    if (!sep) return String(n);
    return n.toLocaleString('id-ID');
  }

  function runCounter(el) {
    const target  = parseInt(el.dataset.count, 10);
    const suffix  = el.dataset.suffix ?? '';
    const sep     = el.dataset.sep ?? '';
    const dur     = 1200;
    const start   = performance.now();

    function tick(now) {
      const p = Math.min((now - start) / dur, 1);
      const eased = 1 - Math.pow(1 - p, 3);
      el.textContent = formatNum(Math.round(eased * target), sep) + suffix;
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  const statsEl = document.querySelector('.sg-stats');
  if (statsEl) {
    const counterObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.querySelectorAll('[data-count]').forEach(runCounter);
          counterObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });
    counterObserver.observe(statsEl);
  }
})();
</script>
</x-public-layout>
