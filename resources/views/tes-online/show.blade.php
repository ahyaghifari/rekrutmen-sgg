<x-app-layout>
<x-slot name="title">{{ $quiz->name }} — Syifa Global Group</x-slot>

<div style="max-width:840px;margin:0 auto;padding:20px 16px;">

  @if(session('success'))
  <div class="mb-4 rounded-xl px-4 py-3 border border-green-200 bg-green-600/[0.07] text-sm font-semibold text-green-700">{{ session('success') }}</div>
  @endif
  @if(session('warning'))
  <div class="mb-4 rounded-xl px-4 py-3 border border-amber-200 bg-amber-500/[0.07] text-sm font-semibold text-amber-700">{{ session('warning') }}</div>
  @endif

  <a href="{{ route('tes-online.show') }}" class="inline-flex items-center gap-2 mb-4 text-[13px] font-semibold text-slate-500 no-underline hover:text-slate-700">
    <i class="fa-solid fa-arrow-left text-[11px]"></i>Semua Tes
  </a>

  <div class="sg-card rounded-[18px] p-7">
    <div class="text-[11.5px] font-bold tracking-[0.08em] uppercase text-slate-500 mb-1.5">Tes</div>
    <h1 class="m-0 mb-2 text-[22px] font-extrabold text-slate-800">{{ $quiz->name }}</h1>

    @if($quiz->instructions)
    <p class="text-[14px] text-slate-600 leading-relaxed mb-4">{{ $quiz->instructions }}</p>
    @endif

    <div class="flex flex-wrap gap-4 text-[13px] text-slate-500 mb-6">
      <div><i class="fa-regular fa-clock mr-1.5"></i>Durasi: {{ $quiz->duration_minutes ?? '—' }} menit sejak dimulai</div>
      <div><i class="fa-solid fa-list-check mr-1.5"></i>{{ $quiz->questions()->count() }} soal</div>
    </div>

    @if(! $attempt)
      <p class="text-[13.5px] text-slate-600 mb-4">Kerjakan kapan saja sebelum batas waktu lamaran. Begitu kamu menekan "Mulai Tes", pengerjaan harus diselesaikan dalam durasi di atas.</p>
      <form method="POST" action="{{ route('tes-online.mulai', $quiz) }}">
        @csrf
        <button type="submit" class="w-full py-[13px] rounded-xl border-0 bg-navy hover:bg-navy-dark text-white text-[14.5px] font-bold cursor-pointer">Mulai Tes</button>
      </form>
    @elseif($attempt->status === 'in_progress')
      <a href="{{ route('tes-online.kerjakan', $quiz) }}" class="block text-center w-full py-[13px] rounded-xl bg-navy hover:bg-navy-dark text-white text-[14.5px] font-bold no-underline">Lanjutkan Mengerjakan</a>
    @elseif($attempt->status === 'submitted')
      <div class="rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-800/40 p-5 text-center">
        <div class="text-[13px] font-bold text-slate-500 uppercase tracking-wide mb-1">Tes Sudah Dikumpulkan</div>
        <div class="text-[12.5px] text-slate-500 mt-1">Dikumpulkan {{ $attempt->submitted_at->translatedFormat('d F Y, H:i') }}</div>
      </div>
      <p class="mt-4 text-[13px] text-slate-500">Hasil tes sudah kami terima. Tim rekrutmen akan menghubungimu untuk tahap selanjutnya.</p>
    @elseif($attempt->status === 'expired')
      <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-950/20 p-5 text-center text-red-700 dark:text-red-400 text-[13.5px] font-semibold">
        Waktu pengerjaan sudah habis sebelum tes dikumpulkan. Hubungi tim rekrutmen kalau ini terjadi karena kendala teknis.
      </div>
    @endif
  </div>

</div>
</x-app-layout>
