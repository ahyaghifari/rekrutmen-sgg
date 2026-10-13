@php
    $files = $application->files ?? [];
    $data  = $application->data ?? [];
    $done  = collect($documents)->keys()->filter(fn ($k) => isset($files[$k]))->count();
    $in    = 'block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-600 file:px-4 file:py-2.5 file:text-white file:font-semibold';
@endphp
<x-public-layout>
<x-slot name="title">Riwayat Lamaran — Portal Dokter</x-slot>

<div class="min-h-screen dokter-bg">
  <div class="h-1 bg-gradient-to-r from-teal-800 via-teal-600 to-teal-400" aria-hidden="true"></div>
  <header class="border-b border-teal-100 bg-white">
    <div class="max-w-[960px] mx-auto px-6 py-[14px] flex items-center gap-4">
      <img src="/logo-sgg.png" alt="Syifa Global Group" class="h-9 w-auto" width="36" height="36" />
      <div class="text-[11px] font-bold tracking-[0.06em] uppercase text-teal-800">Karir Dokter</div>
      <a href="{{ route('dokter.index') }}" class="ml-auto text-sm font-semibold text-slate-600 hover:text-teal-700">Beranda</a>
      <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf
        <button class="px-4 py-2 rounded-xl border border-teal-200 text-sm font-bold text-teal-800 hover:bg-teal-50">Keluar</button>
      </form>
    </div>
  </header>

  <main class="max-w-[960px] mx-auto px-6 pt-8 pb-[72px] space-y-6">
    <h1 class="m-0 text-2xl font-extrabold text-slate-800">Riwayat Lamaran</h1>

    @if (session('status'))
      <div class="rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm font-semibold text-teal-800">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    {{-- Ringkasan --}}
    <section class="rounded-2xl bg-white border border-teal-100 p-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Rumah sakit tujuan</div>
          <div class="mt-1 text-xl font-extrabold text-slate-800">{{ $application->company?->name }}</div>
          <div class="mt-1 text-sm text-slate-500">
            Dokter {{ $application->jenis === 'spesialis' ? 'Spesialis' : 'Umum' }}
            @if ($application->unit_minat) · {{ $application->unit_minat }} @endif
            · dikirim {{ $application->created_at->translatedFormat('d F Y') }}
          </div>
        </div>
        <span class="px-4 py-2 rounded-full bg-teal-50 text-teal-800 text-sm font-bold">{{ $application->statusLabel() }}</span>
      </div>
      <dl class="mt-6 grid gap-x-6 gap-y-4 sm:grid-cols-2 text-sm">
        @foreach ([
          'Nama' => trim(($data['gelar_depan'] ?? '').' '.($data['nama'] ?? '').' '.($data['gelar_belakang'] ?? '')),
          'No. STR' => $data['str_nomor'] ?? null,
          'Spesialisasi' => $data['spesialisasi'] ?? null,
          'Domisili' => trim(($data['dokter_city'] ?? '').', '.($data['dokter_province'] ?? ''), ', '),
        ] as $label => $val)
          @if ($val)
          <div><dt class="font-semibold text-slate-400">{{ $label }}</dt><dd class="m-0 mt-0.5 font-semibold text-slate-800">{{ $val }}</dd></div>
          @endif
        @endforeach
      </dl>
    </section>

    {{-- Berkas --}}
    <section class="rounded-2xl bg-white border border-teal-100 p-8">
      <div class="flex items-baseline justify-between gap-3 mb-1">
        <h2 class="m-0 text-lg font-extrabold text-teal-800"><i class="fa-solid fa-folder-open mr-2" aria-hidden="true"></i>Kelengkapan Berkas</h2>
        <span class="text-sm font-semibold text-slate-500">{{ $done }} dari {{ count($documents) }} terkumpul</span>
      </div>
      <p class="mt-0 mb-5 text-sm text-slate-500">Unggah berkas yang belum lengkap. PDF, JPG, atau PNG, maksimal 5 MB per berkas.</p>
      <ul class="m-0 p-0 list-none divide-y divide-slate-100">
        @foreach ($documents as $key => $label)
          @php $has = isset($files[$key]); @endphp
          <li class="py-4 flex flex-wrap items-center gap-x-4 gap-y-3">
            <div class="flex items-center gap-3 min-w-[220px] flex-1">
              <i class="fa-solid {{ $has ? 'fa-circle-check text-teal-600' : 'fa-circle text-slate-300' }}" aria-hidden="true"></i>
              <div>
                <div class="text-sm font-semibold text-slate-800">{{ $label }} @if(in_array($key, \App\Support\DokterForm::requiredDocuments()))<span class="text-red-500">*</span>@endif</div>
                @if ($has)
                  <a href="{{ route('dokter.berkas', $key) }}" class="text-[12.5px] font-semibold text-teal-700 hover:underline">{{ $files[$key]['name'] }}</a>
                @else
                  <div class="text-[12.5px] text-slate-400">Belum diunggah</div>
                @endif
              </div>
            </div>
            <form method="POST" action="{{ route('dokter.dokumen', $key) }}" enctype="multipart/form-data" class="m-0 flex items-center gap-2" x-data="{ f: false }">
              @csrf
              <input type="file" name="berkas" accept=".pdf,.jpg,.jpeg,.png" required @change="f = $event.target.files.length > 0" class="{{ $in }} max-w-[260px]">
              <button type="submit" x-show="f" x-cloak class="px-4 py-2.5 rounded-lg border-2 border-teal-600 text-teal-700 text-sm font-bold hover:bg-teal-50">{{ $has ? 'Ganti' : 'Unggah' }}</button>
            </form>
          </li>
        @endforeach
      </ul>
    </section>
  </main>
</div>
</x-public-layout>
