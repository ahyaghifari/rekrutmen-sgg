<x-app-layout>
<x-slot name="title">{{ $quiz->name }} — Syifa Global Group</x-slot>

@php
$brand = $application->jobOpening->company?->brand_color ?? '#042c6c';
$applicantName = $application->answersMap()['full_name'] ?? $application->user->name;
@endphp

<div style="max-width:1180px;margin:0 auto;padding:20px 16px;"
     x-data="{
        deadline: new Date('{{ $attempt->deadline_at->toIso8601String() }}').getTime(),
        remaining: 0,
        current: 0,
        total: {{ $questions->count() }},
        answers: {},
        storageKey: 'quiz-draft-{{ $attempt->id }}',
        tick() { this.remaining = Math.max(0, Math.floor((this.deadline - Date.now()) / 1000)); if (this.remaining <= 0) { document.getElementById('quiz-form').submit(); } },
        save() { localStorage.setItem(this.storageKey, JSON.stringify({ answers: this.answers, current: this.current })); },
        init() {
            this.tick(); setInterval(() => this.tick(), 1000);
            try {
                const saved = JSON.parse(localStorage.getItem(this.storageKey));
                if (saved) { this.answers = saved.answers || {}; this.current = saved.current || 0; }
            } catch (e) {}
            this.$watch('answers', () => this.save());
            this.$watch('current', () => this.save());
        },
        next() { this.current = Math.min(this.current + 1, this.total - 1); },
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
      <div class="sg-card rounded-[16px] p-5" x-show="current === {{ $i }}" x-cloak>
        <div class="text-[13px] font-bold text-slate-400 mb-1.5">Soal {{ $i + 1 }} dari {{ $questions->count() }}</div>
        <div class="text-[14.5px] lg:text-[13px] font-semibold text-slate-800 mb-3">{{ $q->question }}</div>
        @if($q->image_url)
        <img src="{{ $q->image_url }}" alt="Gambar soal {{ $i + 1 }}" class="w-full rounded-xl mb-3 border border-slate-200" />
        @endif
        <div class="flex flex-col gap-2">
          @foreach($q->options as $opt)
          <label class="sg-radio-opt flex items-center gap-[10px] border rounded-xl py-3 px-4 text-[13.5px] font-semibold cursor-pointer transition-all duration-150 select-none"
            :class="answers['{{ $q->id }}'] === @js($opt) ? 'sg-radio-checked' : ''"
            :style="answers['{{ $q->id }}'] === @js($opt) ? 'border-color:{{ $brand }};background:{{ $brand }}14;color:{{ $brand }};' : ''">
            <span class="sg-radio-ring relative w-[18px] h-[18px] rounded-full border-2 shrink-0 transition-colors duration-150"
              :style="answers['{{ $q->id }}'] === @js($opt) ? 'border-color:{{ $brand }}' : ''">
              <span class="absolute top-1/2 left-1/2 w-[8px] h-[8px] rounded-full transition-all duration-150"
                style="transform:translate(-50%,-50%) scale(0);"
                :style="answers['{{ $q->id }}'] === @js($opt) ? 'transform:translate(-50%,-50%) scale(1);background:{{ $brand }};' : 'transform:translate(-50%,-50%) scale(0);background:transparent;'"></span>
            </span>
            <input type="radio" name="jawaban[{{ $q->id }}]" value="{{ $opt }}" x-model="answers['{{ $q->id }}']" class="sr-only" />
            {{ $opt }}
          </label>
          @endforeach
        </div>

        <div class="flex items-center justify-between gap-3 mt-5">
          <button type="button" @click="prev()" :disabled="current === 0"
            class="px-4 py-2 rounded-xl border border-slate-200 text-[13.5px] font-bold text-slate-600 disabled:opacity-40 disabled:cursor-not-allowed hover:border-navy transition-colors">
            ← Sebelumnya
          </button>
          <button type="button" @click="next()" x-show="current < total - 1"
            class="px-4 py-2 rounded-xl border-0 bg-navy hover:bg-navy-dark text-white text-[13.5px] font-bold">
            Selanjutnya →
          </button>
        </div>
      </div>
      @endforeach
    </div>

    {{-- Navigasi soal --}}
    <div class="flex flex-col gap-3">
      <div class="sg-card rounded-[16px] p-4">
        <div class="text-[12px] font-bold uppercase tracking-wide text-slate-400 mb-3">Navigasi Soal</div>
        <div class="grid grid-cols-5 lg:grid-cols-4 gap-2 mb-4">
          @foreach($questions as $i => $q)
          <button type="button" @click="current = {{ $i }}"
            class="w-9 h-9 rounded-lg text-[13px] font-bold border transition-colors duration-150"
            :class="current === {{ $i }} ? '' : (answers['{{ $q->id }}'] ? 'border-transparent' : 'border-slate-200 text-slate-500')"
            :style="current === {{ $i }}
              ? 'background:{{ $brand }};border-color:{{ $brand }};color:#fff;'
              : (answers['{{ $q->id }}'] ? 'background:{{ $brand }}14;color:{{ $brand }};' : '')">
            {{ $i + 1 }}
          </button>
          @endforeach
        </div>
        <div class="text-[12px] text-slate-500" x-text="Object.keys(answers).length + ' / ' + total + ' terjawab'"></div>
      </div>

      <button type="submit" :disabled="Object.keys(answers).length < total"
        class="w-full py-[13px] rounded-xl border-0 bg-navy hover:bg-navy-dark text-white text-[14.5px] font-bold cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
        Kumpulkan Jawaban
      </button>
    </div>
  </form>

</div>
</x-app-layout>
