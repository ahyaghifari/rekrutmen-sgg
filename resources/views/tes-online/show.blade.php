<x-app-layout>
<x-slot name="title">Tes Online — Syifa Global Group</x-slot>

<div style="max-width:640px;margin:0 auto;padding:20px 16px;">

  @if(session('success'))
  <div class="mb-4 rounded-xl px-4 py-3 border border-green-200 bg-green-600/[0.07] text-sm font-semibold text-green-700">{{ session('success') }}</div>
  @endif
  @if(session('warning'))
  <div class="mb-4 rounded-xl px-4 py-3 border border-amber-200 bg-amber-500/[0.07] text-sm font-semibold text-amber-700">{{ session('warning') }}</div>
  @endif

  <div class="sg-card rounded-[18px] p-7">
    <div class="text-[11.5px] font-bold tracking-[0.08em] uppercase text-slate-500 mb-1.5">Tes Online</div>
    <h1 class="m-0 mb-2 text-[22px] font-extrabold text-slate-800">{{ $application->jobOpening->title }}</h1>

    @if($application->jobOpening->quiz->instructions)
    <p class="text-[14px] text-slate-600 leading-relaxed mb-4">{{ $application->jobOpening->quiz->instructions }}</p>
    @endif

    <div class="flex flex-wrap gap-4 text-[13px] text-slate-500 mb-6">
      <div><i class="fa-regular fa-clock mr-1.5"></i>Durasi: {{ $application->jobOpening->quiz->duration_minutes ?? '—' }} menit sejak dimulai</div>
      <div><i class="fa-solid fa-list-check mr-1.5"></i>{{ $application->jobOpening->quiz->questions()->count() }} soal</div>
    </div>

    @if(! $attempt)
      <p class="text-[13.5px] text-slate-600 mb-4">Kerjakan kapan saja sebelum batas waktu lamaran. Begitu kamu menekan "Mulai Tes", pengerjaan harus diselesaikan dalam durasi di atas.</p>
      <form method="POST" action="{{ route('tes-online.mulai') }}">
        @csrf
        <button type="submit" class="w-full py-[13px] rounded-xl border-0 bg-navy hover:bg-navy-dark text-white text-[14.5px] font-bold cursor-pointer">Mulai Tes</button>
      </form>
    @elseif($attempt->status === 'in_progress')
      <a href="{{ route('tes-online.kerjakan') }}" class="block text-center w-full py-[13px] rounded-xl bg-navy hover:bg-navy-dark text-white text-[14.5px] font-bold no-underline">Lanjutkan Mengerjakan</a>
    @elseif($attempt->status === 'submitted')
      <div class="rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-800/40 p-5 text-center">
        <div class="text-[13px] font-bold text-slate-500 uppercase tracking-wide mb-1">Skor Kamu</div>
        <div class="text-[32px] font-extrabold text-navy">{{ $attempt->score }}%</div>
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
