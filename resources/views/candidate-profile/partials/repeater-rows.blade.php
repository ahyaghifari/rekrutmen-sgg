{{--
  Baris dengan isian tambahan (extra, mis. pengalaman kerja) tampil sebagai kartu berbingkai di desktop supaya antar entri terpisah jelas.
  Baris berulang (pendidikan, pelatihan, organisasi, kerja, referensi): desktop (md+) = tabel dengan judul kolom, HP = daftar
  kartu 2 kolom dengan ikon hapus di pojok kanan atas. Satu DOM saja (tidak ada isian ganda). Dipakai di dalam
  x-data="repeater(...)".

  $prefix : nama array di form (education, training, ...)
  $grid   : kelas grid desktop (string literal supaya dikenali Tailwind), mis. 'md:grid-cols-[1fr_2fr_3.5rem]'
  $fields : [key, label, type(text|number|date|textarea), span(half|full), m (urutan di HP), head (judul kolom, opsional), attrs, extra(bool)]
            extra = tampil di baris kedua selebar tabel pada desktop (tidak jadi kolom).
--}}
@php
  $main = array_values(array_filter($fields, fn ($f) => empty($f['extra'])));
  // 'hospital' => true: isian khusus rumah sakit (companies.is_hospital), disembunyikan untuk perusahaan lain.
  $extras = array_values(array_filter($fields, fn ($f) => ! empty($f['extra']) && (empty($f['hospital']) || $candidate->isHospital())));
  $lab = 'block mb-1 text-[12px] font-bold text-slate-600';
  $trashOrder = count($main) + 1;
@endphp

<div class="hidden md:grid {{ $grid }} gap-2 {{ $extras ? 'pl-[15px] pr-[13px]' : 'px-1' }} pb-2 text-[12px] font-bold text-slate-500 border-b border-slate-200" x-show="rows.length > 0">
  @foreach($main as $f)<div>{{ $f['head'] ?? $f['label'] }}</div>@endforeach<div></div>
</div>

<template x-for="(row, i) in rows" :key="i">
  <div class="relative grid grid-cols-2 gap-x-3 gap-y-3 rounded-xl border border-slate-200 p-3 pt-10 mb-3 {{ $grid }} md:gap-2 md:items-center {{ $extras ? 'md:rounded-xl md:border md:border-slate-300 md:border-l-[3px] md:border-l-navy md:bg-slate-50 md:p-3 md:pt-3 md:mb-3' : 'md:rounded-none md:border-0 md:border-b md:p-1 md:pt-1 md:pb-2 md:mb-0' }}">
    @foreach($main as $n => $f)
    <div class="{{ ($f['span'] ?? 'half') === 'full' ? 'col-span-2 md:col-span-1' : '' }} order-{{ $f['m'] ?? $n + 1 }} md:order-{{ $n + 1 }}">
      <label class="md:hidden {{ $lab }}">{{ $f['label'] }}</label>
      <input type="{{ $f['type'] ?? 'text' }}" {!! $f['attrs'] ?? '' !!} :name="'{{ $prefix }}['+i+'][{{ $f['key'] }}]'" x-model="row.{{ $f['key'] }}" class="form-field fam-field" />
    </div>
    @endforeach

    <button type="button" @click="remove(i)" title="Hapus" aria-label="Hapus"
      class="absolute top-2 right-2 md:static md:order-{{ $trashOrder }} md:justify-self-center grid place-items-center w-8 h-8 rounded-lg border-0 bg-transparent text-slate-400 hover:text-slate-600 hover:bg-slate-100 cursor-pointer"><i class="fa-solid fa-trash-can text-[13px]" aria-hidden="true"></i></button>

    @foreach($extras as $n => $f)
    <div class="col-span-2 md:col-span-full order-last" @if(! empty($f['show'])) x-show="{{ $f['show'] }}" @endif>
      <label class="{{ $lab }}">{{ $f['label'] }}</label>
      @if(($f['type'] ?? 'text') === 'textarea')
      <textarea :name="'{{ $prefix }}['+i+'][{{ $f['key'] }}]'" x-model="row.{{ $f['key'] }}" rows="2" class="form-field fam-field resize-y"></textarea>
      @else
      <input type="{{ $f['type'] ?? 'text' }}" :name="'{{ $prefix }}['+i+'][{{ $f['key'] }}]'" x-model="row.{{ $f['key'] }}" class="form-field fam-field" />
      @endif
    </div>
      @if($prefix === 'work' && $f['key'] === 'resign_reason')
      <div class="col-span-2 md:col-span-full order-last flex items-center gap-2">
        <input type="hidden" :name="'work['+i+'][is_current]'" :value="row.is_current ? 1 : 0" />
        <input type="checkbox" x-model="row.is_current" :id="'cur'+i" />
        <label :for="'cur'+i" class="text-[13px] text-slate-600">Masih bekerja di sini</label>
      </div>
      @endif
    @endforeach
  </div>
</template>
