{{-- Respons pelamar atas jadwal (wawancara / MCU). Param: $invitee, $type (wawancara|mcu), $mode: top = form kuning di paling atas beranda (belum dijawab), inline = ringkasan kecil di bawah kartu jadwal (sudah dijawab). Hanya sekali. --}}
@php
  $sch = $invitee?->schedule;
  $schDate = $type === 'mcu' ? $sch?->mcu_date : $sch?->interview_date;
  $labels = ['attend' => 'Hadir', 'reschedule' => 'Reschedule (ajukan jadwal ulang)', 'absent_with_reason' => 'Tidak hadir dengan alasan'];
  $field = 'w-full rounded-lg border border-amber-300 bg-white px-3 py-2.5 text-slate-700 text-[13px] md:text-[15px] focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-300';
@endphp
@if($mode === 'top' && $invitee && ! $invitee->responded_at && $sch && $sch->status === 'published' && $schDate && ! $schDate->lt(today()))
<div class="mt-5 rounded-[18px] border border-amber-200 bg-amber-50/60 px-5 py-4 md:px-7 md:py-6 text-[13px] md:text-[15px] text-slate-700"
     x-data="{ resp: '{{ old('response') }}', confirm: false }"
     x-effect="document.body.classList.toggle('overflow-hidden', confirm)">
  <div class="font-extrabold text-[14px] md:text-[17px] mb-2 text-slate-800">
    <i class="fa-solid fa-circle-exclamation mr-1.5 text-amber-500"></i>Konfirmasi Kehadiran {{ $type === 'mcu' ? 'Medical Check Up' : 'Wawancara' }}
  </div>
  @php
    $schTime = $type === 'mcu' ? $sch->mcu_time : $sch->interview_time;
    $rows = [
      'Posisi' => $invitee->jobApplication->jobOpening->title . ' · ' . $invitee->jobApplication->jobOpening->company_name,
      'Jadwal Anda' => $schDate->translatedFormat('l, j F Y') . ($schTime ? ' · ' . substr($schTime, 0, 5) . ' WITA' : ''),
    ];
  @endphp
  <table class="mb-3 md:mb-4">
    @foreach($rows as $label => $value)
    <tr class="align-top">
      <td class="pr-2 py-0.5 whitespace-nowrap text-slate-500">{{ $label }}</td>
      <td class="pr-2 py-0.5 text-slate-500">:</td>
      <td class="py-0.5 font-semibold text-slate-800">{{ $value }}</td>
    </tr>
    @endforeach
  </table>

  <form id="resp-{{ $type }}" method="POST" action="{{ route('jadwal.respons', [$type, $invitee->id]) }}">
    @csrf
    <div class="grid gap-2 md:gap-3 md:grid-cols-3">
      @foreach($labels as $val => $text)
      <label class="flex items-center gap-3 cursor-pointer rounded-xl border-2 px-4 py-3 md:py-4 font-semibold transition"
             :class="resp === '{{ $val }}' ? 'border-amber-400 bg-amber-100/70' : 'border-amber-200 bg-white hover:border-amber-300'">
        <input type="radio" name="response" value="{{ $val }}" x-model="resp" class="sr-only">
        <span class="grid h-5 w-5 md:h-6 md:w-6 shrink-0 place-items-center rounded-full border-2 bg-white"
              :class="resp === '{{ $val }}' ? 'border-amber-500' : 'border-amber-300'">
          <span class="h-2.5 w-2.5 md:h-3 md:w-3 rounded-full bg-amber-500 transition" :class="resp === '{{ $val }}' ? 'scale-100' : 'scale-0'"></span>
        </span>
        {{ $text }}
      </label>
      @endforeach
    </div>
    @error('response')<div class="mt-1 text-red-600 text-[12px]">{{ $message }}</div>@enderror

    <div x-show="resp === 'absent_with_reason' || resp === 'reschedule'" x-cloak class="mt-3">
      <textarea name="response_reason" rows="2" maxlength="500" placeholder="Tulis alasan Anda" class="{{ $field }}">{{ old('response_reason') }}</textarea>
      @error('response_reason')<div class="mt-1 text-red-600 text-[12px]">{{ $message }}</div>@enderror
    </div>
    <div x-show="resp === 'reschedule'" x-cloak class="mt-3">
      <div class="flex flex-wrap gap-2">
        <input type="date" name="proposed_date" value="{{ old('proposed_date') }}" min="{{ today()->addDay()->toDateString() }}" class="{{ $field }} !w-auto">
        <input type="time" name="proposed_time" value="{{ old('proposed_time') }}" class="{{ $field }} !w-auto">
      </div>
      @error('proposed_date')<div class="mt-1 text-red-600 text-[12px]">{{ $message }}</div>@enderror
      @error('proposed_time')<div class="mt-1 text-red-600 text-[12px]">{{ $message }}</div>@enderror
      <div class="mt-3 flex items-start gap-2.5 rounded-xl border-l-4 border-amber-400 bg-amber-100 px-4 py-3 text-[13px] md:text-[14.5px] font-bold text-amber-900"><i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-500"></i><span>Jadwal reschedule yang diajukan perlu disetujui terlebih dahulu oleh sistem. Jika tidak disetujui silahkan bisa hadir sesuai jadwal awal yang sudah ditentukan</span></div>
    </div>

    <button type="button" :disabled="! resp" @click="confirm = true"
            class="mt-4 w-full rounded-xl bg-amber-300 px-4 py-2.5 md:py-3 text-[13.5px] md:text-[15px] font-extrabold text-amber-950 hover:bg-amber-400 disabled:opacity-40 disabled:cursor-not-allowed">Kirim Jawaban</button>

    <template x-teleport="body"><div x-show="confirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="confirm = false">
      <div class="w-full max-w-sm rounded-2xl bg-white p-5 text-slate-700 shadow-xl">
        <div class="font-extrabold text-slate-800 mb-1">Kirim jawaban?</div>
        <p class="text-[13px] mb-4">Jawaban hanya dapat dikirim satu kali dan tidak dapat diubah setelah dikirim.</p>
        <div class="flex gap-2">
          <button type="button" @click="confirm = false" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-[13px] font-semibold">Batal</button>
          <button type="submit" form="resp-{{ $type }}" class="flex-1 rounded-lg bg-amber-300 px-3 py-2 text-[13px] font-bold text-amber-950 hover:bg-amber-400">Ya, Kirim</button>
        </div>
      </div>
    </div></template>
  </form>
</div>
@endif

{{-- Sudah dijawab: ringkasan satu blok kecil di bawah kartu jadwal, supaya tidak memakan tempat. --}}
@if($mode === 'inline' && $invitee?->responded_at)
@php
  $stText = match (true) {
    $invitee->response !== 'reschedule' => null,
    $invitee->reschedule_status === 'approved' => 'Disetujui',
    $invitee->reschedule_status === 'rejected' => 'Tidak disetujui, silakan hadir sesuai jadwal awal',
    default => 'Menunggu persetujuan',
  };
@endphp
<div class="mt-3 rounded-[12px] border border-amber-200 bg-amber-50/60 px-4 py-2.5 text-[12.5px] md:text-[13px] text-slate-700">
  <table>
    <tr class="align-top"><td class="pr-2 whitespace-nowrap text-slate-500">Jawaban Anda</td><td class="pr-2 text-slate-500">:</td><td class="font-semibold text-slate-800">{{ $labels[$invitee->response] ?? '-' }}</td></tr>
    @if($invitee->response_reason)<tr class="align-top"><td class="pr-2 whitespace-nowrap text-slate-500">Alasan</td><td class="pr-2 text-slate-500">:</td><td>{{ $invitee->response_reason }}</td></tr>@endif
    @if($invitee->response === 'reschedule')
    <tr class="align-top"><td class="pr-2 whitespace-nowrap text-slate-500">Jadwal diajukan</td><td class="pr-2 text-slate-500">:</td><td>{{ $invitee->proposed_date?->translatedFormat('l, j F Y') }} &middot; {{ substr($invitee->proposed_time, 0, 5) }} WITA</td></tr>
    <tr class="align-top"><td class="pr-2 whitespace-nowrap text-slate-500">Status</td><td class="pr-2 text-slate-500">:</td><td class="font-semibold text-slate-800">{{ $stText }}</td></tr>
    @endif
  </table>
</div>
@endif
