<x-app-layout>
<x-slot name="title">Persetujuan — Syifa Global Group</x-slot>

<div x-data="{
        total: {{ $effectiveAgreements->count() }},
        checked: 0,
        get done() { return this.total > 0 && this.checked >= this.total; },
        get pct() { return this.total ? Math.round(this.checked / this.total * 100) : 0; },
     }"
     x-init="checked = $root.querySelectorAll('input[type=checkbox]:checked').length">

  @if(session('success'))
  <div class="mb-4 rounded-2xl px-4 py-3 bg-green-600/[0.08] text-[13px] font-semibold text-green-700 flex items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>{{ session('success') }}
  </div>
  @endif

  @if(session('warning'))
  <div class="mb-4 rounded-2xl px-4 py-3 bg-amber-500/[0.1] text-[13px] font-semibold text-amber-700 flex items-center gap-2">
    <i class="fa-solid fa-triangle-exclamation"></i>{{ session('warning') }}
  </div>
  @endif

  @if($errors->any())
  <div class="mb-4 rounded-2xl px-4 py-3 bg-red-600/[0.07]">
    <div class="text-[13px] font-bold text-red-700 mb-1">Periksa kembali isian kamu:</div>
    <ul class="text-[12.5px] text-red-600 list-disc pl-4">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
  @endif

  <h1 class="m-0 mb-1 text-[19px] font-extrabold text-slate-800 tracking-[-0.3px]">Persetujuan</h1>
  <p class="m-0 text-[12px] leading-[1.55] text-slate-500">
    @if($locked)
      Kamu sudah menyetujui semua naskah di bawah. Halaman ini hanya untuk dibaca kembali.
    @else
      Baca setiap naskah lalu tandai persetujuanmu. Semua harus disetujui untuk melanjutkan proses onboarding.
    @endif
  </p>

  @if($locked)
  @php($agreedAt = $candidate->agreements->max('agreed_at'))
  <div class="mt-4 rounded-2xl px-4 py-3 bg-emerald-50 text-[12px] font-semibold text-emerald-700 flex flex-wrap items-center gap-x-2 gap-y-1">
    <span class="flex items-center gap-2"><i class="fa-solid fa-lock text-[11px]"></i>Persetujuan terkunci — sudah lengkap dan tidak bisa diubah lagi.</span>
    @if($agreedAt)
    <span class="text-[11px] font-medium text-emerald-600/90">· Disetujui {{ $agreedAt->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB</span>
    @endif
  </div>
  @endif

  @if($effectiveAgreements->isNotEmpty() && ! $locked)
  {{-- Progres --}}
  <div class="mt-4 mb-5 flex items-center gap-3">
    <div class="flex-1 h-1.5 rounded-full overflow-hidden" style="background:#e8edf5;">
      <div class="h-full rounded-full transition-all duration-300"
           style="background:linear-gradient(90deg,#042c6c,#047C54);" :style="`width:${pct}%`"></div>
    </div>
    <span class="text-[11px] font-bold text-slate-500 tabular-nums whitespace-nowrap"
          x-text="`${checked} / ${total} disetujui`"></span>
  </div>
  @endif

  <form method="POST" action="{{ route('persetujuan.update') }}" class="flex flex-col gap-5">
    @csrf
    @method('PATCH')

    {{-- Semua PHP inline pakai bentuk @php(...) — jangan pakai bentuk blok di file ini
         (penutup bloknya bakal menutup @php(...) inline pertama & merusak kompilasi Blade). --}}
    @php($agreed = $candidate->agreements->keyBy(fn ($a) => $a->company_agreement_id ? 'c:' . $a->company_agreement_id : ($a->job_opening_agreement_id ? 'j:' . $a->job_opening_agreement_id : 'x')))

    @forelse($effectiveAgreements as $i => $agreement)
    @php($key = $agreement['company_agreement_id'] ? 'c:' . $agreement['company_agreement_id'] : 'j:' . $agreement['job_opening_agreement_id'])
    @php($snap = $agreed->get($key))
    @php($title = $snap?->title ?? $agreement['title'])
    @php($content = $snap?->content ?? $agreement['content'])

    <div x-data="{ on: {{ $snap ? 'true' : 'false' }} }"
         class="sg-card rounded-[20px] overflow-hidden transition-shadow duration-200"
         :class="on ? 'ring-1 ring-emerald-300 shadow-[0_4px_20px_-6px_rgba(4,124,84,.25)]' : 'shadow-sm'">

      {{-- Header --}}
      <div class="flex items-start gap-2.5 px-5 pt-4 pb-3">
        <div class="w-6 h-6 shrink-0 rounded-[8px] grid place-items-center text-[11px] font-extrabold mt-[1px]"
             style="color:#042c6c;background:#042c6c14;" x-show="!on">{{ $i + 1 }}</div>
        <div class="w-6 h-6 shrink-0 rounded-[8px] grid place-items-center text-white mt-[1px]"
             style="background:#047C54;" x-show="on" x-cloak>
          <i class="fa-solid fa-check text-[11px]"></i>
        </div>
        <div class="min-w-0 flex-1">
          <h2 class="m-0 text-[13.5px] font-extrabold text-slate-800 leading-snug">{{ $title }}</h2>
        </div>
      </div>

      {{-- Naskah --}}
      <div class="px-5">
        <div class="rounded-[12px] bg-slate-50 border border-slate-100 px-4 py-3 text-[11.5px] leading-[1.7] text-slate-600 max-h-[260px] overflow-y-auto
                    [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-2 [&_strong]:text-slate-800 [&_h1]:font-bold [&_h2]:font-bold">
          {!! $content !!}
        </div>
      </div>

      {{-- Persetujuan --}}
      <label class="mt-3 flex items-center gap-2.5 px-5 py-3 border-t transition-colors duration-150 {{ $locked ? 'pointer-events-none' : 'cursor-pointer' }}"
             :class="on ? 'bg-emerald-50/70 border-emerald-100' : 'bg-white border-slate-100 {{ $locked ? '' : 'hover:bg-slate-50' }}'">
        <span class="relative w-[18px] h-[18px] shrink-0 rounded-[6px] border-2 grid place-items-center transition-colors duration-150"
              :class="on ? 'bg-emerald-500 border-emerald-500' : 'border-slate-300 bg-white'">
          <i class="fa-solid fa-check text-[10px] text-white transition-opacity duration-150" :class="on ? 'opacity-100' : 'opacity-0'"></i>
        </span>
        <input type="checkbox" name="agreements[{{ $key }}]" value="1" {{ $snap ? 'checked' : '' }} @disabled($locked) class="sr-only"
               x-on:change="on = $event.target.checked; checked += on ? 1 : -1" />
        <span class="text-[11.5px] leading-[1.5] text-slate-600">
          Saya telah membaca, memahami, dan menyetujui <strong class="text-slate-800">{{ $title }}</strong>.
        </span>
      </label>
    </div>
    @empty
    <div class="sg-card rounded-[16px] px-6 py-8 text-center">
      <div class="w-10 h-10 mx-auto mb-2.5 rounded-full grid place-items-center" style="background:#042c6c0d;">
        <i class="fa-regular fa-circle-check text-[16px]" style="color:#042c6c;"></i>
      </div>
      <div class="text-[12.5px] font-bold text-slate-700 mb-0.5">Tidak ada persetujuan</div>
      <p class="m-0 text-[11.5px] text-slate-500">Perusahaan belum menambahkan naskah persetujuan. Bagian ini bisa dilewati.</p>
    </div>
    @endforelse

    @if($effectiveAgreements->isNotEmpty() && ! $locked)
    <div class="sticky bottom-4 z-10">
      <div class="sg-card rounded-[14px] px-4 py-3 flex items-center justify-between gap-3 shadow-[0_8px_30px_-8px_rgba(0,0,0,.18)]">
        <span class="text-[11px] font-semibold"
              :class="done ? 'text-emerald-600' : 'text-slate-500'"
              x-text="done ? 'Semua persetujuan sudah ditandai.' : 'Tandai semua persetujuan untuk melanjutkan.'"></span>
        <button type="submit" :disabled="!done"
                class="shrink-0 px-5 py-2 rounded-[10px] border-0 bg-sgg-green hover:bg-sgg-green-hover text-white text-[12px] font-bold cursor-pointer transition-opacity disabled:opacity-40 disabled:cursor-not-allowed">
          Simpan Persetujuan
        </button>
      </div>
    </div>
    @endif

  </form>

</div>
</x-app-layout>
