@php
    $pre = ['nama' => auth()->user()->name, 'nik' => auth()->user()->nik, 'email' => auth()->user()->email, 'no_hp' => auth()->user()->phone];
    $in = 'w-full rounded-xl border-2 border-slate-300 px-4 py-3.5 text-base focus:border-teal-600 focus:ring-teal-600';
    $lb = 'block text-sm font-semibold text-slate-700 mb-2';
    $sections  = \App\Support\DokterForm::sections();
    $repeaters = \App\Support\DokterForm::repeaters();
@endphp
<x-public-layout>
<x-slot name="title">Lamaran Dokter — Syifa Global Group</x-slot>

<div class="min-h-screen bg-teal-50/40" x-data="{ jenis: '{{ $jenis }}' }">
  <div class="h-1 bg-gradient-to-r from-teal-800 via-teal-600 to-teal-400" aria-hidden="true"></div>
  <header class="border-b border-teal-100 bg-white">
    <div class="max-w-[1180px] mx-auto px-6 py-[14px] flex items-center gap-4">
      <img src="/logo-sgg.png" alt="Syifa Global Group" class="h-9 w-auto" width="36" height="36" />
      <div class="text-[11px] font-bold tracking-[0.06em] uppercase text-teal-800">Portal Dokter</div>
      <a href="{{ route('dokter.index') }}" class="ml-auto text-sm font-semibold text-slate-600 hover:text-teal-700"><i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i> Kembali</a>
      <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="px-4 py-2 rounded-xl border border-teal-200 text-sm font-bold text-teal-800 hover:bg-teal-50">Keluar</button></form>
    </div>
  </header>

  <main class="max-w-[1180px] mx-auto px-6 pt-8 pb-[72px]">
    <h1 class="m-0 text-2xl font-extrabold text-slate-800">Formulir Lamaran Dokter</h1>
    <p class="mt-1 mb-6 text-sm text-slate-500">Dokter umum melamar ke rumah sakit yang sedang membuka penerimaan. Dokter spesialis mengajukan diri langsung ke rumah sakit.</p>

    <form x-data="{ ok: false }" x-init="setInterval(() => ok = $el.checkValidity(), 300)" method="POST" action="{{ route('dokter.kirim') }}" enctype="multipart/form-data" class="space-y-8">
      @csrf
      @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><ul class="m-0 pl-5">@foreach ($errors->all() as $er)<li>{{ $er }}</li>@endforeach</ul></div>
      @endif
      {{-- Jenis dokter: umum = RS yang buka penerimaan, spesialis = melamar langsung ke RS --}}
      <section class="rounded-2xl bg-white border border-teal-100 p-8">
        <div class="inline-flex rounded-xl bg-teal-50 p-1 mb-4" role="tablist">
          <button type="button" @click="jenis='umum'" :class="jenis==='umum' ? 'bg-teal-600 text-white' : 'text-teal-800'" class="px-4 py-2 rounded-lg text-sm font-bold">Dokter Umum</button>
          <button type="button" @click="jenis='spesialis'" :class="jenis==='spesialis' ? 'bg-teal-600 text-white' : 'text-teal-800'" class="px-4 py-2 rounded-lg text-sm font-bold">Dokter Spesialis</button>
        </div>
        <input type="hidden" name="jenis" :value="jenis">
        <div x-show="jenis==='umum'">
          <label class="{{ $lb }}">Rumah sakit yang membuka penerimaan <span class="text-red-500">*</span></label>
          <select name="company_id" class="{{ $in }}" :disabled="jenis!=='umum'" required>
            <option value="">— Pilih rumah sakit —</option>
            @foreach ($umumOpen as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
          </select>
          @if ($umumOpen->isEmpty())<p class="mt-2 text-xs text-slate-500">Penerimaan dokter umum sedang ditutup di semua rumah sakit.</p>@endif
        </div>
        <div x-show="jenis==='spesialis'" x-cloak class="grid gap-x-6 gap-y-7 md:grid-cols-2">
          <div>
            <label class="{{ $lb }}">Rumah sakit tujuan <span class="text-red-500">*</span></label>
            <select name="company_id" class="{{ $in }}" :disabled="jenis!=='spesialis'" required>
              <option value="">— Pilih rumah sakit —</option>
              @foreach ($hospitals as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </select>
          </div>
          <div>
            <label class="{{ $lb }}">Poli / unit yang diminati</label>
            <input type="text" name="unit_minat" class="{{ $in }}" :disabled="jenis!=='spesialis'">
          </div>
        </div>
      </section>

      @foreach ($sections as [$title, $icon, $fields])
        <section class="rounded-2xl bg-white border border-teal-100 p-8">
          <h2 class="m-0 mb-6 text-lg font-extrabold text-teal-800"><i class="fa-solid {{ $icon }} mr-2" aria-hidden="true"></i>{{ $title }}</h2>
          <div class="grid gap-x-6 gap-y-7 md:grid-cols-6">
            @foreach ($fields as $fl)
              <div class="{{ $fl['type'] === 'textarea' ? 'md:col-span-6' : (in_array($fl['name'], ['gelar_depan', 'nama', 'gelar_belakang']) ? 'md:col-span-2' : 'md:col-span-3') }}" @if($fl['extra'] === 'spesialis') x-show="jenis==='spesialis'" @endif>
                <label class="{{ $lb }}">{{ $fl['label'] }} @if($fl['req'])<span class="text-red-500">*</span>@endif</label>
                @if ($fl['type'] === 'select')
                  <select name="{{ $fl['name'] }}" class="{{ $in }}" @required($fl['req']) @if($fl['extra'] === 'spesialis') :disabled="jenis!=='spesialis'" @endif>
                    <option value="">— Pilih —</option>@foreach ($fl['opts'] as $op)<option>{{ $op }}</option>@endforeach
                  </select>
                @elseif ($fl['type'] === 'textarea')
                  <textarea name="{{ $fl['name'] }}" rows="2" class="{{ $in }}" @required($fl['req'])></textarea>
                @elseif ($fl['type'] === 'file')
                  <input type="file" name="{{ $fl['name'] }}" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-600 file:px-4 file:py-3 file:text-white file:font-semibold" @required($fl['req'])>
                @else
                  <input type="{{ $fl['type'] }}" name="{{ $fl['name'] }}" value="{{ $pre[$fl['name']] ?? '' }}" class="{{ $in }}" @required($fl['req']) @if($fl['extra'] === 'spesialis') :disabled="jenis!=='spesialis'" @endif>
                @endif
              </div>
            @endforeach
          </div>
          @if ($title === 'Identitas')
            <x-wilayah-picker prefix="dokter_" :count="2" accent="teal" :big="true" class="mt-7 gap-x-6 gap-y-7" />
          @endif
        </section>
      @endforeach

      @foreach ($repeaters as [$key, $title, $icon, $cols, $wajib])
        <section class="rounded-2xl bg-white border border-teal-100 p-8" x-data="{ rows: [0] }">
          <h2 class="m-0 mb-6 text-lg font-extrabold text-teal-800"><i class="fa-solid {{ $icon }} mr-2" aria-hidden="true"></i>{{ $title }}</h2>
          <template x-for="(r, idx) in rows" :key="r">
            <div class="mb-4 rounded-xl border border-slate-200 p-4">
              <div class="grid gap-x-6 gap-y-7 md:grid-cols-2">
                @foreach ($cols as $c)
                  <div>
                    <label class="{{ $lb }}">{{ $c[1] }} @if(in_array($c[0], $wajib))<span class="text-red-500">*</span>@endif</label>
                    <input type="{{ $c[2] ?? 'text' }}" :name="'{{ $key }}['+idx+'][{{ $c[0] }}]'" class="{{ $in }}" @required(in_array($c[0], $wajib))>
                  </div>
                @endforeach
                <div>
                  <label class="{{ $lb }}">Berkas pendukung</label>
                  <input type="file" :name="'{{ $key }}['+idx+'][berkas]'" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-600 file:px-4 file:py-3 file:text-white file:font-semibold">
                </div>
              </div>
              <button type="button" x-show="rows.length > 1" @click="rows.splice(idx,1)" class="mt-3 text-xs font-semibold text-red-600">Hapus baris</button>
            </div>
          </template>
          <button type="button" @click="rows.push(Date.now())" class="text-sm font-bold text-teal-700"><i class="fa-solid fa-plus text-xs" aria-hidden="true"></i> Tambah</button>
        </section>
      @endforeach

      <section class="rounded-2xl bg-white border border-teal-100 p-8">
        <label class="flex items-start gap-3 text-sm text-slate-700">
          <input type="checkbox" required class="mt-1 rounded border-2 border-slate-300 text-teal-600 focus:ring-teal-600">
          Saya menyatakan data benar, mematuhi kode etik kedokteran, dan tidak sedang menjalani sanksi pelanggaran etik.
        </label>
      </section>

      <div class="text-center"><button type="submit" :disabled="!ok" class="px-8 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold transition-colors disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-teal-600">Kirim lamaran</button></div>
    </form>
  </main>
</div>
</x-public-layout>
