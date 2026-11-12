{{-- Scan STR (opsional): bisa diunggah di biodata atau menyusul saat onboarding. Dipasang di blok STR (biodata) dan tab BPJS & Rekening (biodata sudah dibekukan) - tidak pernah dua-duanya. Dropzone seperti unggah offering; file dikirim bersama form (bukan form sendiri). Warna lewat style inline supaya tidak bergantung build Tailwind. --}}
<div class="col-span-full" x-data="{ name: '', over: false,
    pick(files) { if (files.length) { $refs.file.files = files; this.name = files[0].name; } },
    clear() { this.name = ''; $refs.file.value = ''; } }">
  <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Scan STR <span class="font-normal text-slate-400">(PDF/JPG/PNG, maks. 5 MB)</span></label>
  @if($candidate->str_file_path)
    <p class="mb-2 text-[12.5px] font-semibold text-emerald-700"><i class="fa-solid fa-circle-check mr-1"></i>File STR sudah diunggah. Pilih file baru hanya jika ingin menggantinya.</p>
  @endif

  <label class="flex flex-col items-center justify-center gap-1 rounded-[14px] border-2 border-dashed px-4 py-6 text-center cursor-pointer transition-colors"
    :style="over ? 'background:#f0f9ff;border-color:#38bdf8;' : (name ? 'background:#ecfdf5;border-color:#34d399;' : 'background:#f8fafc;border-color:#cbd5e1;')"
    @dragover.prevent="over = true" @dragleave.prevent="over = false" @drop.prevent="over = false; pick($event.dataTransfer.files)">
    <i class="fa-solid text-[24px]" :class="name ? 'fa-file-circle-check text-emerald-600' : 'fa-cloud-arrow-up text-slate-400'"></i>
    <span class="text-[13px] font-bold text-slate-600" x-show="!name">Tarik file ke sini atau klik untuk memilih</span>
    <span class="text-[13px] font-bold text-emerald-700 break-all" x-show="name" x-text="name" x-cloak></span>
    <span class="text-[11.5px] text-slate-400" x-show="!name">Scan STR yang masih berlaku</span>
    <input type="file" name="str_file" x-ref="file" accept=".pdf,.jpg,.jpeg,.png" class="sr-only" @change="pick($event.target.files)">
  </label>
  <button type="button" x-show="name" x-cloak @click="clear()" class="mt-2 bg-transparent border-0 underline cursor-pointer text-[12px] text-slate-500">Batal pilih</button>
  @error('str_file')<p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>@enderror
</div>
