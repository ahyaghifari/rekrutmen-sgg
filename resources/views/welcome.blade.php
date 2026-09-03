<x-public-layout>
<x-slot name="title">Portal Rekrutmen — Syifa Global Group</x-slot>

<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:rounded-lg" style="background:#042c6c;color:#fff;">Langsung ke konten</a>

<div class="sg-rise min-h-screen">

  {{-- Top bar --}}
  <div class="h-1" style="background:linear-gradient(90deg,#042c6c 0%,#0b47a1 55%,#B9308C 100%);" aria-hidden="true"></div>

  {{-- Navbar --}}
  <header class="sg-navbar-header border-b">
    <div class="max-w-[1180px] mx-auto px-6 py-[14px] flex items-center gap-7">
      <div class="flex items-center gap-3">
        <img src="/logo-sgg.png" alt="Syifa Global Group" class="h-9 w-auto" width="36" height="36" />
        <div class="text-[11px] font-bold tracking-[0.06em] uppercase text-navy">Karir & Rekrutmen</div>
      </div>

      {{-- Desktop nav --}}
      <nav class="sg-desk-flex gap-1.5 ml-auto items-center" aria-label="Navigasi utama">
        <a href="{{ route('welcome') }}" class="px-[14px] py-2 rounded-[10px] text-sm font-semibold text-navy bg-navy/[0.07]" aria-current="page">Beranda</a>
        <a href="#lowongan" class="px-[14px] py-2 rounded-[10px] text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors duration-150">Lowongan</a>
        @auth
          <a href="{{ route('dashboard') }}" class="ml-2 px-5 py-[10px] rounded-[11px] bg-navy text-white text-sm font-bold no-underline hover:bg-navy-dark transition-colors duration-150">Dashboard</a>
        @else
          <a href="{{ route('login') }}" class="ml-1 px-5 py-[10px] rounded-[11px] bg-navy text-white text-sm font-bold no-underline hover:bg-navy-dark transition-colors duration-150">Masuk</a>
        @endauth
      </nav>

      {{-- Mobile --}}
      <div class="sg-mob ml-auto" style="display:flex;align-items:center;gap:8px;">
        @auth
          <a href="{{ route('dashboard') }}" class="px-4 py-[9px] rounded-[11px] bg-navy text-white text-[13.5px] font-bold no-underline">Dashboard</a>
        @else
          <a href="{{ route('login') }}" class="px-4 py-[9px] rounded-[11px] bg-navy text-white text-[13.5px] font-bold no-underline">Masuk</a>
        @endauth
      </div>
    </div>
  </header>

  <main id="main-content" class="max-w-[1180px] mx-auto px-6 pt-8 pb-[72px]">

    {{-- Hero --}}
    <section x-data="heroSlider" class="relative overflow-hidden rounded-[20px] text-white" aria-labelledby="hero-heading">
      {{-- Gradient backgrounds crossfade --}}
      <template x-for="(slide, i) in slides" :key="i">
        <div class="absolute inset-0 transition-opacity duration-[900ms] ease-in-out"
             :style="`background:linear-gradient(135deg,${slide.color1} 0%,${slide.color2} 100%);opacity:${active===i?1:0}`"
             aria-hidden="true"></div>
      </template>

      {{-- Decorative shapes --}}
      <div class="absolute w-[220px] h-[220px] rounded-[40px] rotate-[24deg] -left-[80px] -bottom-[80px] pointer-events-none bg-white/[0.06] z-[1]" aria-hidden="true"></div>
      <div class="absolute w-[140px] h-[140px] rounded-full -right-[40px] -top-[50px] pointer-events-none bg-white/[0.05] z-[1]" aria-hidden="true"></div>
      <div class="absolute w-[70px] h-[70px] rounded-[18px] rotate-[12deg] right-[15%] bottom-[12%] pointer-events-none bg-white/[0.04] z-[1]" aria-hidden="true"></div>
      <div class="absolute w-[90px] h-[90px] rounded-full left-[30%] top-[10%] pointer-events-none bg-white/[0.03] z-[1]" aria-hidden="true"></div>

      <div class="sg-hero-wrap relative z-[2]">
        <div class="sg-hero flex-1 min-w-0 flex flex-col justify-center">
          <div class="inline-flex items-center gap-2 px-[14px] py-[7px] rounded-full text-xs font-bold tracking-[0.05em] uppercase bg-white/[0.14] w-fit">
            <i class="fa-solid fa-briefcase" aria-hidden="true"></i>
            {{ $openings->count() }} posisi dibuka saat ini
          </div>
          <h1
            id="hero-heading"
            x-data="{
              lines: ['Join and Grow', 'With Us'],
              out: ['',''],
              init() {
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
            class="sg-h1 mt-5 mb-3 leading-[1.1] font-extrabold tracking-[-1.2px]"
            style="text-wrap: balance;"
          >
            <span x-text="out[0]"></span><span x-show="out[0].length < 13" class="sg-cursor" aria-hidden="true">|</span><br>
            <span x-text="out[1]" style="font-family:'Lora',serif;font-style:italic;font-weight:700;"></span><span x-show="out[0].length >= 13" class="sg-cursor" aria-hidden="true">|</span>
          </h1>
          <p class="m-0 mb-4 text-[15px] text-white/80 max-w-[380px] leading-relaxed">Bergabunglah dengan keluarga besar Syifa Global Group dan bangun karier yang bermakna.</p>
          <div class="flex flex-wrap gap-3">
            <a href="#lowongan" class="inline-flex items-center gap-2 px-[22px] py-[12px] rounded-[11px] text-sm font-bold no-underline transition-all duration-500"
               :style="`background:#fff;color:${slides[active]?.color2 ?? '#042c6c'};touch-action:manipulation;`">
              Lihat Lowongan <i class="fa-solid fa-arrow-down text-xs" aria-hidden="true"></i>
            </a>
            @guest
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-[22px] py-[12px] rounded-[11px] text-sm font-bold no-underline transition-colors duration-150" style="background:rgba(255,255,255,0.15);color:#fff;touch-action:manipulation;">
              Daftar &amp; Lamar <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
            @endguest
          </div>

          {{-- Slide dots --}}
          <div class="flex gap-[6px] mt-6" role="tablist" aria-label="Slide perusahaan">
            <template x-for="(slide, i) in slides" :key="i">
              <button
                @click="goTo(i)"
                :aria-label="slide.name"
                :aria-selected="active === i"
                role="tab"
                class="h-[6px] rounded-full bg-white transition-all duration-300 ease-in-out"
                :style="`width: ${active===i ? '24px' : '6px'}; opacity: ${active===i ? 1 : 0.4};`"
                style="touch-action:manipulation;border:0;padding:0;cursor:pointer;"
              ></button>
            </template>
          </div>
        </div>

        {{-- Hero images crossfade --}}
        <div class="sg-hero-right relative" aria-hidden="true">
          {{-- Logo watermark per slide --}}
          <template x-for="(slide, i) in slides" :key="'logo-'+i">
            <img
              x-show="slide.logo"
              :src="slide.logo"
              :alt="slide.name"
              class="absolute transition-opacity duration-[900ms] ease-in-out pointer-events-none select-none"
              :style="`bottom:0;right:0;width:110%;height:110%;object-fit:contain;object-position:bottom right;opacity:${active===i?0.09:0};`"
            />
          </template>
          {{-- Hero photos --}}
          <template x-for="(slide, i) in slides" :key="'img-'+i">
            <img
              :src="slide.image"
              :alt="slide.name"
              class="sg-hero-img transition-opacity duration-[900ms] ease-in-out"
              :style="`position:${i===0?'relative':'absolute'};top:0;left:0;opacity:${active===i?1:0};`"
              width="520" height="420"
            />
          </template>
        </div>
      </div>
    </section>

    {{-- Lowongan --}}
    <section id="lowongan" class="mt-14" aria-labelledby="lowongan-heading">
      <div class="sg-fade flex items-baseline justify-between mb-5">
        <h2 id="lowongan-heading" class="m-0 text-xl font-extrabold text-slate-800 tracking-[-0.4px]">Lowongan Terbuka</h2>
        <span class="text-[13.5px] font-semibold text-slate-500">{{ $openings->count() }} posisi tersedia</span>
      </div>
      @if($openings->isEmpty())
      <div class="sg-card rounded-[16px] px-6 py-10 text-center">
        <div class="w-12 h-12 rounded-full grid place-items-center mx-auto mb-3" style="background:rgba(4,44,108,.07);">
          <i class="fa-solid fa-briefcase text-navy text-[19px]"></i>
        </div>
        <div class="text-[14.5px] font-bold text-slate-800 mb-1">Belum ada lowongan yang dibuka</div>
        <p class="m-0 text-[13px] text-slate-500">Pantau terus halaman ini, lowongan baru akan segera hadir.</p>
      </div>
      @else
      <div class="sg-job-grid">
        @foreach($openings as $opening)
          <x-job-card :opening="$opening" :index="$loop->index" />
        @endforeach
      </div>
      @endif
    </section>

    {{-- Alur Rekrutmen --}}
    <section class="mt-24" aria-labelledby="alur-heading">
      <div class="sg-fade text-center max-w-[620px] mx-auto mb-[34px]">
        <div class="text-[11.5px] font-bold tracking-[0.1em] uppercase text-slate-500">Alur Rekrutmen</div>
        <h2 id="alur-heading" class="mt-2.5 mb-2 text-[26px] font-extrabold text-slate-800 tracking-[-0.6px]" style="text-wrap: balance;">Enam tahap yang jelas dan terstruktur</h2>
        <p class="m-0 text-[14.5px] leading-[1.6] text-slate-500">Setiap perpindahan tahap dapat dipantau kapan saja dari dashboard kandidat.</p>
      </div>
      <div class="sg-tl">
        @foreach([
          ['icon' => 'fa-plus',         'bg' => 'bg-navy',      'border' => '',                    'color' => 'text-white', 'label' => 'Daftar &amp; Lamar',  'desc' => 'Buat akun dan isi formulir lamaran',     'last' => false],
          ['icon' => 'fa-circle-check', 'bg' => 'bg-white',     'border' => 'border-2 border-navy','color' => 'text-navy',  'label' => 'Seleksi Berkas',       'desc' => 'Tim HR meninjau kelengkapan dokumen',    'last' => false, 'regular' => true],
          ['icon' => 'fa-laptop',       'bg' => 'bg-white',     'border' => 'border-2 border-navy','color' => 'text-navy',  'label' => 'Tes',           'desc' => 'Kerjakan tes lewat portal',              'last' => false],
          ['icon' => 'fa-user-group',   'bg' => 'bg-white',     'border' => 'border-2 border-navy','color' => 'text-navy',  'label' => 'Wawancara',            'desc' => 'Jadwal dikirim via WhatsApp',            'last' => false],
          ['icon' => 'fa-stethoscope',  'bg' => 'bg-white',     'border' => 'border-2 border-navy','color' => 'text-navy',  'label' => 'MCU',                  'desc' => 'Medical check up',                       'last' => false],
          ['icon' => 'fa-circle-check', 'bg' => 'bg-sgg-green', 'border' => '',                    'color' => 'text-white', 'label' => 'TTD &amp; Onboarding', 'desc' => 'Kontrak dan mulai berkarier',            'last' => true],
        ] as $i => $step)
        <div class="sg-fade relative text-center" style="transition-delay: {{ $i * 0.1 }}s;">
          @if(!$step['last'])
          <div class="sg-tl-line absolute top-[33px] border-t-2 border-dashed border-slate-300" style="left:calc(50% + 40px);right:calc(-50% + 40px);" aria-hidden="true"></div>
          @endif
          <div class="relative z-[1] w-[66px] h-[66px] mx-auto rounded-full grid place-items-center text-[19px] {{ $step['bg'] }} {{ $step['border'] }} {{ $step['color'] }}" aria-hidden="true">
            <i class="{{ isset($step['regular']) ? 'fa-regular' : 'fa-solid' }} {{ $step['icon'] }}"></i>
          </div>
          <div class="mt-4 text-[15px] font-extrabold {{ $step['last'] ? 'text-sgg-green' : 'text-slate-800' }}" aria-hidden="false">{!! $step['label'] !!}</div>
          <div class="mt-[5px] text-[13px] leading-[1.5] text-slate-600">{!! $step['desc'] !!}</div>
        </div>
        @endforeach
      </div>
    </section>

    {{-- Butuh bantuan --}}
    <section class="sg-fade mt-24 relative overflow-hidden rounded-[20px] text-white flex flex-wrap gap-7 items-center justify-between" style="background:linear-gradient(135deg,#047C54 0%,#024732 100%);padding:clamp(28px,5vw,40px) clamp(24px,5vw,44px);" aria-labelledby="help-heading">
      <div class="absolute w-[280px] h-[280px] rounded-full -right-[60px] -top-[140px] pointer-events-none bg-white/[0.06]" aria-hidden="true"></div>
      <div class="absolute w-[160px] h-[160px] rounded-full -left-[60px] -bottom-[80px] pointer-events-none bg-white/[0.04]" aria-hidden="true"></div>
      <div class="relative z-[1]">
        <div class="text-[11.5px] font-bold tracking-[0.1em] uppercase text-white/70 mb-[10px]">Hubungi Tim Rekrutmen</div>
        <h2 id="help-heading" class="m-0 mb-[10px] text-[26px] font-extrabold tracking-[-0.6px]">Butuh bantuan?</h2>
        <p class="m-0 text-[15px] leading-[1.6] text-white/[0.82] max-w-[480px]">Tim rekrutmen kami siap menjawab pertanyaan seputar lowongan dan status lamaran pada hari kerja, 08.00–16.00 WIB.</p>
      </div>
      <div class="relative z-[1] flex flex-col gap-3">
        @forelse($contacts as $contact)
          @if($contact->email)
            <a href="mailto:{{ $contact->email }}" class="flex items-center gap-3 px-5 py-[14px] rounded-[14px] no-underline text-white bg-white/[0.14] hover:bg-white/[0.22] transition-colors duration-150" style="touch-action:manipulation;">
              <div class="w-9 h-9 shrink-0 rounded-[10px] grid place-items-center text-sm bg-white/[0.18]" aria-hidden="true"><i class="fa-regular fa-envelope"></i></div>
              <span class="text-sm font-bold">{{ $contact->name }} &middot; {{ $contact->email }}</span>
            </a>
          @endif
          @if($contact->phone)
            <a href="https://wa.me/{{ preg_replace('/\D/', '', $contact->phone) }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 px-5 py-[14px] rounded-[14px] no-underline text-white bg-white/[0.14] hover:bg-white/[0.22] transition-colors duration-150" style="touch-action:manipulation;">
              <div class="w-9 h-9 shrink-0 rounded-[10px] grid place-items-center text-sm bg-white/[0.18]" aria-hidden="true"><i class="fa-brands fa-whatsapp"></i></div>
              <span class="text-sm font-bold">{{ $contact->name }} &middot; {{ $contact->phone }}</span>
            </a>
          @endif
        @empty
          <a href="mailto:recruiter@syifaglobalgroup.com" class="flex items-center gap-3 px-5 py-[14px] rounded-[14px] no-underline text-white bg-white/[0.14] hover:bg-white/[0.22] transition-colors duration-150" style="touch-action:manipulation;">
            <div class="w-9 h-9 shrink-0 rounded-[10px] grid place-items-center text-sm bg-white/[0.18]" aria-hidden="true"><i class="fa-regular fa-envelope"></i></div>
            <span class="text-sm font-bold">recruiter@syifaglobalgroup.com</span>
          </a>
          <a href="https://wa.me/6282253568275" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 px-5 py-[14px] rounded-[14px] no-underline text-white bg-white/[0.14] hover:bg-white/[0.22] transition-colors duration-150" style="touch-action:manipulation;">
            <div class="w-9 h-9 shrink-0 rounded-[10px] grid place-items-center text-sm bg-white/[0.18]" aria-hidden="true"><i class="fa-brands fa-whatsapp"></i></div>
            <span class="text-sm font-bold">WhatsApp &middot; 0822-5356-8275</span>
          </a>
        @endforelse
      </div>
    </section>

  </main>

  {{-- Footer --}}
  <footer class="sg-footer border-t px-6 pt-[34px] pb-16">
    <div class="max-w-[1180px] mx-auto flex flex-wrap gap-5 items-center justify-between">
      <div class="text-[13px] text-slate-500">&copy; {{ date('Y') }} Syifa Global Group &middot; Seluruh proses rekrutmen tidak dipungut biaya apa pun.</div>
      <div class="flex gap-5 text-[13px] font-semibold">
        <a href="#" class="text-slate-500 hover:text-slate-700">Kebijakan Privasi</a>
        <a href="#" class="text-slate-500 hover:text-slate-700">Syarat &amp; Ketentuan</a>
        <a href="#" class="text-slate-500 hover:text-slate-700">Hubungi HR</a>
      </div>
    </div>
  </footer>

</div>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('heroSlider', () => ({
    slides: @json($slides),
    active: 0,
    timer: null,
    init() { this.timer = setInterval(() => this.next(), 8000); },
    next() { this.active = (this.active + 1) % this.slides.length; },
    goTo(i) { clearInterval(this.timer); this.active = i; this.timer = setInterval(() => this.next(), 8000); }
  }));
});
</script>
</x-public-layout>
