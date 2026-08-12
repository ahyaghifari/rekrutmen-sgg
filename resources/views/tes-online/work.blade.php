<x-app-layout>
<x-slot name="title">Mengerjakan Tes — Syifa Global Group</x-slot>

<div style="max-width:640px;margin:0 auto;padding:20px 16px;"
     x-data="{ deadline: new Date('{{ $attempt->deadline_at->toIso8601String() }}').getTime(), remaining: 0, tick() { this.remaining = Math.max(0, Math.floor((this.deadline - Date.now()) / 1000)); if (this.remaining <= 0) { document.getElementById('quiz-form').submit(); } }, init() { this.tick(); setInterval(() => this.tick(), 1000); } }"
     x-init="init()">

  <div class="sg-card rounded-[18px] p-6 mb-5 flex items-center justify-between sticky top-4 z-10" style="background:#042c6c;color:#fff;">
    <div class="text-[13px] font-bold uppercase tracking-wide text-white/70">Sisa Waktu</div>
    <div class="text-[22px] font-extrabold tabular-nums"
         x-text="String(Math.floor(remaining/60)).padStart(2,'0') + ':' + String(remaining%60).padStart(2,'0')"></div>
  </div>

  <form id="quiz-form" method="POST" action="{{ route('tes-online.submit') }}" class="flex flex-col gap-4">
    @csrf

    @foreach($questions as $i => $q)
    <div class="sg-card rounded-[16px] p-5">
      <div class="text-[13px] font-bold text-slate-400 mb-1.5">Soal {{ $i + 1 }} dari {{ $questions->count() }}</div>
      <div class="text-[14.5px] font-semibold text-slate-800 mb-3">{{ $q->question }}</div>
      @if($q->image_url)
      <img src="{{ $q->image_url }}" alt="Gambar soal {{ $i + 1 }}" class="w-full rounded-xl mb-3 border border-slate-200" />
      @endif
      <div class="flex flex-col gap-2">
        @foreach($q->options as $opt)
        <label class="flex items-center gap-2.5 rounded-lg border border-slate-200 px-3.5 py-2.5 text-[13.5px] text-slate-700 cursor-pointer hover:border-navy transition-colors">
          <input type="radio" name="jawaban[{{ $q->id }}]" value="{{ $opt['label'] }}" required class="accent-[#042c6c]" />
          {{ $opt['label'] }}
        </label>
        @endforeach
      </div>
    </div>
    @endforeach

    <button type="submit" class="w-full py-[13px] rounded-xl border-0 bg-navy hover:bg-navy-dark text-white text-[14.5px] font-bold cursor-pointer">Kumpulkan Jawaban</button>
  </form>

</div>
</x-app-layout>
