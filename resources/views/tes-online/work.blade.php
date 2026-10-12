<x-app-layout>
<x-slot name="title">{{ $quiz->name }} — Syifa Global Group</x-slot>

@php
$brand = $application->jobOpening->company?->brand_color ?? '#042c6c';
$applicantName = $application->answersMap()['full_name'] ?? $application->user->name;

// Kuis bersubtest: satu halaman per subtest. Tanpa subtest: satu soal per halaman.
$sectioned = $questions->contains(fn ($q) => $q->quiz_section_id);
$pageOf = []; $localNo = []; $pageSize = []; $pageIds = []; $p = -1; $prevSection = null; $n = 0;
foreach ($questions as $i => $q) {
    if (! $sectioned || $i === 0 || $q->quiz_section_id !== $prevSection) $p++;
    $prevSection = $q->quiz_section_id;
    $pageOf[$i] = $p;
    $n = ($i > 0 && $pageOf[$i - 1] === $p) ? $n + 1 : 1;
    $localNo[$i] = $sectioned ? $n : $i + 1;
    $pageSize[$p] = $n;
    $pageIds[$p][] = $q->id;
}
$pages = $p + 1;
@endphp

<div style="max-width:1180px;margin:0 auto;padding:20px 16px;"
     x-data="{
        deadline: new Date('{{ $attempt->deadline_at->toIso8601String() }}').getTime(),
        remaining: 0,
        current: 0,
        total: {{ $questions->count() }},
        pages: {{ $pages }},
        pageIds: @js($pageIds),
        pageAnswered() { return (this.pageIds[this.current] || []).filter(k => this.has(k)).length; },
        answers: { @foreach($questions->where('max_select', '>', 0) as $mq) '{{ $mq->id }}': [], @endforeach },
        has(k) { const v = this.answers[k]; return Array.isArray(v) ? v.length > 0 : !!v; },
        answered() { return Object.keys(this.answers).filter(k => this.has(k)).length; },
        confirmOpen: false,
        storageKey: 'quiz-draft-{{ $attempt->id }}',
        submitQuiz() { localStorage.removeItem(this.storageKey); document.getElementById('quiz-form').submit(); },
        tick() { this.remaining = Math.max(0, Math.floor((this.deadline - Date.now()) / 1000)); if (this.remaining <= 0) { this.submitQuiz(); } },
        save() { localStorage.setItem(this.storageKey, JSON.stringify({ answers: this.answers, current: this.current })); },
        init() {
            this.tick(); setInterval(() => this.tick(), 1000);
            try {
                const saved = JSON.parse(localStorage.getItem(this.storageKey));
                if (saved) { this.answers = Object.assign(this.answers, saved.answers || {}); this.current = saved.current || 0; }
            } catch (e) {}
            this.$watch('answers', () => this.save());
            this.$watch('current', () => { this.save(); window.scrollTo({ top: 0 }); });
        },
        next() { this.current = Math.min(this.current + 1, this.pages - 1); },
        prev() { this.current = Math.max(this.current - 1, 0); }
     }"
     x-init="init()">

  <div class="sg-card rounded-[18px] p-6 mb-5 flex items-center justify-between gap-4" style="background:#042c6c;color:#fff;">
    <div class="min-w-0">
      <div class="text-[15px] font-extrabold truncate">{{ $quiz->name }}</div>
      <div class="text-[12.5px] text-white/70 mt-0.5 truncate">{{ $application->jobOpening->title }} &middot; {{ $applicantName }}</div>
    </div>
    <div class="text-right shrink-0">
      <div class="text-[11px] font-bold uppercase tracking-wide text-white/70">Sisa Waktu</div>
      <div class="text-[22px] font-extrabold tabular-nums"
           x-text="String(Math.floor(remaining/60)).padStart(2,'0') + ':' + String(remaining%60).padStart(2,'0')"></div>
    </div>
  </div>

  <form id="quiz-form" method="POST" action="{{ route('tes-online.submit', $quiz) }}" @submit="localStorage.removeItem(storageKey)" class="grid grid-cols-1 lg:grid-cols-[1fr_240px] gap-4 items-start">
    @csrf

    <div class="flex flex-col gap-4">
      @foreach($questions as $i => $q)
      <div id="q-{{ $q->id }}" class="sg-card rounded-[16px] p-5" x-show="current === {{ $pageOf[$i] }}" x-cloak>
        @if($q->section && ($i === 0 || $pageOf[$i] !== $pageOf[$i - 1]))
        <div class="text-[12px] font-extrabold uppercase tracking-wide mb-1" style="color:{{ $brand }};">{{ $q->section->name }}</div>
        @if($q->section->instructions)
        <p class="text-[13px] text-slate-600 leading-relaxed mb-2">{{ $q->section->instructions }}</p>
        @endif
        @endif
        <div class="text-[13px] font-bold text-slate-400 mb-1.5">Soal {{ $localNo[$i] }} dari {{ $sectioned ? $pageSize[$pageOf[$i]] : $questions->count() }}</div>
        <div class="text-[14.5px] lg:text-[13px] font-semibold text-slate-800 mb-3">{{ $q->question }}</div>
        @if($q->image_url)
        <img src="{{ $q->image_url }}" alt="Gambar soal {{ $localNo[$i] }}" class="w-full rounded-xl mb-3 border border-slate-200" />
        @endif
        <div class="flex flex-col gap-2">
          @foreach($q->options as $opt)
          <label class="sg-radio-opt flex items-center gap-[10px] border rounded-xl py-3 px-4 text-[13.5px] font-semibold cursor-pointer transition-all duration-150 select-none"
            :class="@if($q->max_select)answers['{{ $q->id }}'].includes(@js($opt))@else answers['{{ $q->id }}'] === @js($opt)@endif ? 'sg-radio-checked' : ''"
            :style="@if($q->max_select)answers['{{ $q->id }}'].includes(@js($opt))@else answers['{{ $q->id }}'] === @js($opt)@endif ? 'border-color:{{ $brand }};background:{{ $brand }}14;color:{{ $brand }};' : ''">
            <span class="sg-radio-ring relative w-[18px] h-[18px] rounded-full border-2 shrink-0 transition-colors duration-150"
              :style="@if($q->max_select)answers['{{ $q->id }}'].includes(@js($opt))@else answers['{{ $q->id }}'] === @js($opt)@endif ? 'border-color:{{ $brand }}' : ''">
              <span class="absolute top-1/2 left-1/2 w-[8px] h-[8px] rounded-full transition-all duration-150"
                style="transform:translate(-50%,-50%) scale(0);"
                :style="@if($q->max_select)answers['{{ $q->id }}'].includes(@js($opt))@else answers['{{ $q->id }}'] === @js($opt)@endif ? 'transform:translate(-50%,-50%) scale(1);background:{{ $brand }};' : 'transform:translate(-50%,-50%) scale(0);background:transparent;'"></span>
            </span>
            @if($q->max_select)
            <input type="checkbox" name="jawaban[{{ $q->id }}][]" value="{{ $opt }}" x-model="answers['{{ $q->id }}']"
              :disabled="answers['{{ $q->id }}'].length >= {{ $q->max_select }} && !answers['{{ $q->id }}'].includes(@js($opt))" class="sr-only" />
            @else
            <input type="radio" name="jawaban[{{ $q->id }}]" value="{{ $opt }}" x-model="answers['{{ $q->id }}']" class="sr-only" />
            @endif
            @if($q->optionImageUrl($opt))
            <img src="{{ $q->optionImageUrl($opt) }}" alt="Pilihan {{ $opt }}" class="max-h-40 rounded-lg border border-slate-200" />
            @endif
            {{ $opt }}
          </label>
          @endforeach
        </div>

        @if($i === $questions->count() - 1 || $pageOf[$i + 1] !== $pageOf[$i])
        <div class="flex items-center justify-between gap-3 mt-5">
          <button type="button" @click="prev()" :disabled="current === 0"
            class="px-4 py-2 rounded-xl border border-slate-200 text-[13.5px] font-bold text-slate-600 disabled:opacity-40 disabled:cursor-not-allowed hover:border-navy transition-colors">
            ← Sebelumnya
          </button>
          <button type="button" @click="next()" x-show="current < pages - 1"
            class="px-4 py-2 rounded-xl border-0 bg-navy hover:bg-navy-dark text-white text-[13.5px] font-bold">
            {{ $sectioned ? 'Subtest Berikutnya →' : 'Selanjutnya →' }}
          </button>
        </div>
        @endif
      </div>
      @endforeach
    </div>

    {{-- Navigasi soal --}}
    <div class="flex flex-col gap-3">
      <div class="sg-card rounded-[16px] p-4">
        <div class="text-[12px] font-bold uppercase tracking-wide text-slate-400 mb-3">Navigasi Soal</div>
        <div class="grid grid-cols-5 lg:grid-cols-4 gap-2 mb-4">
          @foreach($questions as $i => $q)
          <button type="button"
            @if($sectioned) x-show="current === {{ $pageOf[$i] }}" @click="document.getElementById('q-{{ $q->id }}').scrollIntoView({ behavior: 'smooth', block: 'start' })"
            @else @click="current = {{ $pageOf[$i] }}" @endif
            class="w-9 h-9 rounded-lg text-[13px] font-bold border transition-colors duration-150"
            :class="{{ $sectioned ? 'false' : 'current === ' . $pageOf[$i] }} ? '' : (has('{{ $q->id }}') ? 'border-transparent' : 'border-slate-200 text-slate-500')"
            :style="{{ $sectioned ? 'false' : 'current === ' . $pageOf[$i] }}
              ? 'background:{{ $brand }};border-color:{{ $brand }};color:#fff;'
              : (has('{{ $q->id }}') ? 'background:{{ $brand }}14;color:{{ $brand }};' : '')">
            {{ $localNo[$i] }}
          </button>
          @endforeach
        </div>
        @if($sectioned)
        <div class="text-[12px] text-slate-500 mb-1" x-text="pageAnswered() + ' / ' + (pageIds[current] || []).length + ' terjawab di subtest ini'"></div>
        @endif
        <div class="text-[12px] text-slate-500" x-text="answered() + ' / ' + total + ' terjawab total'"></div>
      </div>

      <button type="button" @click="confirmOpen = true" :disabled="answered() < total"
        class="w-full py-[13px] rounded-xl border-0 bg-navy hover:bg-navy-dark text-white text-[14.5px] font-bold cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
        Kumpulkan Jawaban
      </button>
    </div>
  </form>

  {{-- Modal konfirmasi kumpulkan jawaban --}}
  <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
       x-transition.opacity>
    <div class="absolute inset-0 bg-slate-900/50" @click="confirmOpen = false"></div>
    <div class="relative sg-card rounded-[18px] p-6 w-full max-w-[400px]" @keydown.escape.window="confirmOpen = false">
      <div class="text-[16px] font-extrabold text-slate-800 mb-1">Kumpulkan jawaban?</div>
      <p class="text-[13px] leading-[1.6] text-slate-500 m-0 mb-5">
        Setelah dikumpulkan, jawaban tidak bisa diubah lagi. Pastikan semua soal sudah kamu jawab dengan yakin.
      </p>
      <div class="flex justify-end gap-2">
        <button type="button" @click="confirmOpen = false"
          class="px-4 py-[10px] rounded-xl border border-slate-200 text-slate-600 text-[13px] font-bold cursor-pointer bg-white">
          Batal
        </button>
        <button type="button" @click="submitQuiz()"
          class="px-5 py-[10px] rounded-xl border-0 bg-navy hover:bg-navy-dark text-white text-[13px] font-bold cursor-pointer">
          Ya, Kumpulkan
        </button>
      </div>
    </div>
  </div>

</div>
</x-app-layout>
