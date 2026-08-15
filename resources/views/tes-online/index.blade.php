<x-app-layout>
<x-slot name="title">Tes Online — Syifa Global Group</x-slot>

<div style="max-width:640px;margin:0 auto;padding:20px 16px;">

  @if(session('success'))
  <div class="mb-4 rounded-xl px-4 py-3 border border-green-200 bg-green-600/[0.07] text-sm font-semibold text-green-700">{{ session('success') }}</div>
  @endif

  <div class="mb-5">
    <div class="text-[11.5px] font-bold tracking-[0.08em] uppercase text-slate-500 mb-1.5">Tes Online</div>
    <h1 class="m-0 mb-1 text-[22px] font-extrabold text-slate-800">{{ $application->jobOpening->title }}</h1>
    <p class="m-0 text-[13.5px] text-slate-500">{{ $quizzes->count() }} tes perlu kamu selesaikan untuk posisi ini.</p>
  </div>

  <div class="flex flex-col gap-3">
    @foreach($quizzes as $quiz)
    @php $attempt = $attempts->get($quiz->id); @endphp
    <a href="{{ route('tes-online.detail', $quiz) }}" class="sg-card rounded-[16px] p-5 flex items-center gap-4 no-underline hover:border-navy transition-colors">
      <div class="w-11 h-11 shrink-0 rounded-[12px] grid place-items-center" style="background:rgba(4,44,108,.08);">
        <i class="fa-solid fa-laptop text-navy"></i>
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-[14.5px] font-bold text-slate-800">{{ $quiz->name }}</div>
        <div class="text-[12.5px] text-slate-500 mt-0.5">
          {{ $quiz->duration_minutes ? $quiz->duration_minutes.' menit' : 'Tanpa batas waktu' }} · {{ $quiz->questions()->count() }} soal
        </div>
      </div>
      @php
        $badge = match ($attempt?->status) {
          'submitted'   => ['Selesai', 'background:rgba(4,124,84,.1);color:#047C54;'],
          'in_progress' => ['Sedang dikerjakan', 'background:rgba(245,158,11,.1);color:#b45309;'],
          'expired'     => ['Waktu habis', 'background:rgba(239,68,68,.1);color:#dc2626;'],
          default       => ['Belum mulai', 'background:rgba(100,116,139,.1);color:#475569;'],
        };
      @endphp
      <span class="shrink-0 px-3 py-[6px] rounded-full text-[11.5px] font-bold" style="{{ $badge[1] }}">{{ $badge[0] }}</span>
    </a>
    @endforeach
  </div>

</div>
</x-app-layout>
