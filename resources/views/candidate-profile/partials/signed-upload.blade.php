{{-- Unggah biodata bertanda tangan (cetak PDF, tanda tangan, unggah). Hanya sekali. Dipakai di dashboard dan halaman biodata. --}}
@php $signedCand = auth()->user()->candidate; @endphp
@if($signedCand?->biodata_signed_path)
  <div class="text-[12.5px]">
    <div class="font-bold mb-1">Biodata bertanda tangan</div>
    <div>
      <i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i>Sudah diunggah {{ $signedCand->biodata_signed_at?->translatedFormat('j F Y H:i') }} WITA &middot;
      <a href="{{ route('data-diri.biodata.signed') }}" target="_blank" rel="noopener" class="font-bold underline">Lihat</a>
    </div>
    <div class="mt-1 opacity-80">Pengiriman hanya sekali, jadi tidak bisa diunggah ulang.</div>
  </div>
@else
<form method="POST" action="{{ route('data-diri.biodata.upload-signed') }}" enctype="multipart/form-data" class="text-[12.5px]"
  x-data="{ name: '', over: false, ask: false,
    pick(files) { if (files.length) { $refs.file.files = files; this.name = files[0].name; } } }">
  @csrf
  <div class="font-bold mb-1">Unggah biodata bertanda tangan</div>
  <div class="mb-2 opacity-80">Cetak PDF biodata, tanda tangani, lalu unggah (PDF/JPG/PNG, maks. 5 MB). <strong>Pengiriman hanya sekali</strong> dan tidak bisa diunggah ulang.</div>

  <label class="flex flex-col items-center justify-center gap-1 rounded-[12px] border-2 border-dashed px-4 py-5 text-center cursor-pointer transition-colors"
    :class="over ? 'bg-white border-solid' : 'bg-white/60'" style="border-color:currentColor;"
    @dragover.prevent="over = true" @dragleave.prevent="over = false" @drop.prevent="over = false; pick($event.dataTransfer.files)">
    <i class="fa-solid fa-cloud-arrow-up text-[22px]"></i>
    <span class="font-bold" x-show="!name">Tarik file ke sini atau klik untuk memilih</span>
    <span class="font-bold break-all" x-show="name" x-text="name" x-cloak></span>
    <input type="file" name="file" x-ref="file" accept=".pdf,.jpg,.jpeg,.png" required class="sr-only" @change="pick($event.target.files)">
  </label>

  <div class="mt-2 flex flex-col items-stretch gap-2">
    <button type="button" @click="ask = true" x-bind:disabled="!name" class="sg-job-card-btn w-full justify-center text-center border-0 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">Unggah</button>
    <button type="button" x-show="name" x-cloak @click="name = ''; $refs.file.value = ''" class="self-center bg-transparent border-0 underline cursor-pointer text-[12px]" style="color:inherit;">Batal pilih</button>
  </div>
  @error('file')<div class="mt-1 text-red-600">{{ $message }}</div>@enderror

  {{-- Popup konfirmasi sebelum benar-benar diunggah --}}
  <div x-show="ask" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="background:rgba(15,23,42,.55);" @keydown.escape.window="ask = false">
    <div class="w-full max-w-[420px] rounded-[16px] bg-white p-5 text-slate-700 shadow-xl" @click.outside="ask = false">
      <div class="text-[15px] font-extrabold text-slate-800 mb-2"><i class="fa-solid fa-triangle-exclamation text-amber-600 mr-1.5"></i>Unggah biodata sekarang?</div>
      <p class="m-0 mb-1 text-[13px]">File: <strong class="break-all" x-text="name"></strong></p>
      <p class="m-0 mb-4 text-[13px]">Pengiriman hanya <strong>sekali</strong>. Setelah diunggah, file tidak bisa diganti lagi. Pastikan biodata sudah benar dan sudah ditandatangani.</p>
      <div class="flex justify-end gap-2">
        <button type="button" @click="ask = false" class="px-4 py-2 rounded-[10px] border border-slate-200 bg-white text-[13px] font-bold text-slate-600 cursor-pointer">Periksa lagi</button>
        <button type="submit" class="px-4 py-2 rounded-[10px] border-0 bg-navy text-white text-[13px] font-bold cursor-pointer">Ya, unggah</button>
      </div>
    </div>
  </div>
</form>
@endif
