<x-app-layout>
<x-slot name="title">{{ $onboarding ? 'Data Diri Kandidat' : 'Biodata Pelamar' }} — Syifa Global Group</x-slot>

@php
// ponytail: @json() Blade directive does a naive explode(',', ...) on its argument,
// so an inline array literal with 4+ items gets silently truncated. Precompute into
// plain variables here so each @json() call below has zero top-level commas.
// Tanggal dikirim 'Y-m-d': serialisasi bawaan model berupa ISO datetime, yang tidak bisa dibaca <input type="date">
// (kolom tampil kosong dan tersimpan kosong saat form disimpan ulang).
$ymd = fn ($d) => $d?->format('Y-m-d');
$money = fn ($v) => $v === null ? null : rtrim(rtrim((string) $v, '0'), '.'); // 3000000.00 -> 3000000 (input number tidak terpotong)
$familyMembersData  = $candidate->familyMembers->map(fn ($m) => array_merge($m->only(['relation','gender','name','education','job','phone']), ['birth_date' => $ymd($m->birth_date)]));
$educationsData     = $candidate->educations->map->only(['level','school_name','major','gpa','start_year','end_year']);
$trainingsData      = $candidate->trainings->map->only(['type','institution','material','duration','location']);
$organizationsData  = $candidate->organizations->map->only(['organization_name','field','position','duration','location']);
$workExperiencesData = $candidate->workExperiences->map(fn ($w) => array_merge($w->only(['company_name','business_type','company_address','position','start_date','end_date','salary_start','salary','resign_reason','is_current','job_description','org_position_description']), ['start_date' => $ymd($w->start_date), 'end_date' => $ymd($w->end_date), 'salary_start' => $money($w->salary_start), 'salary' => $money($w->salary)]));
$referencesData     = $candidate->references->map->only(['name','address','job','phone','relation']);
// Setelah validasi gagal, baris berulang harus tetap seperti yang diketik pelamar, bukan dimuat ulang dari DB (yang masih kosong
// kalau belum pernah tersimpan) — kalau tidak, semua isian keluarga/pendidikan/pekerjaan tampak hilang.
foreach (['family' => 'familyMembersData', 'education' => 'educationsData', 'training' => 'trainingsData', 'organization' => 'organizationsData', 'work' => 'workExperiencesData', 'reference' => 'referencesData'] as $oldKey => $var) {
    if (is_array(old($oldKey))) {
        $$var = collect(old($oldKey))->values()->map(fn ($r) => $oldKey === 'work' ? array_merge((array) $r, ['is_current' => (bool) ($r['is_current'] ?? false)]) : $r);
    }
}
@endphp

<div>

  @if(session('success'))
  <div class="mb-4 rounded-xl px-4 py-3 border border-green-200 bg-green-600/[0.07] text-sm font-semibold text-green-700">{{ session('success') }}</div>
  @endif

  @if($errors->any())
  @php
    // Tab tempat galat pertama berada (baris berulang saja; isian biasa tetap di tab pertama).
    $errTab = ['family' => 4, 'education' => 5, 'training' => 6, 'organization' => 7, 'work' => 8, 'reference' => 9][\Illuminate\Support\Str::before($errors->keys()[0], '.')] ?? null;
  @endphp
  <script>document.addEventListener('alpine:initialized', () => {
    const f = document.getElementById('biodata-form'), t = @json($errTab);
    if (f && t && Alpine.$data(f).order.includes(t)) Alpine.$data(f).tab = t;
    document.getElementById('form-errors')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });</script>
  <div id="form-errors" class="mb-4 rounded-xl px-4 py-3 border border-red-200 bg-red-600/[0.06]">
    <div class="text-sm font-bold text-red-700 mb-1">Data belum tersimpan. Periksa kembali isian Anda:</div>
    <ul class="text-[12.5px] text-red-600 list-disc pl-4">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
  @endif

  <h1 class="m-0 mb-1 text-[22px] font-extrabold text-slate-800">{{ $onboarding ? 'Data Diri Kandidat' : 'Biodata Pelamar' }}</h1>
  <p class="m-0 mb-5 text-[13.5px] text-slate-500">
    @if($onboarding)
      Lengkapi data ini untuk menyelesaikan proses rekrutmen — identitas resmi, keluarga, pendidikan, BPJS, rekening, dan dokumen pendukung.
    @else
      Anda sudah dijadwalkan wawancara. Lengkapi biodata ini sebelum wawancara — identitas, alamat, keluarga, pendidikan, dan pengalaman.
    @endif
  </p>
  <div class="mb-5 flex flex-wrap gap-2">
    <a href="{{ route('data-diri.biodata') }}" target="_blank" rel="noopener" class="px-4 py-2 rounded-[10px] border border-slate-200 bg-white text-[13px] font-bold text-slate-700 no-underline hover:border-navy"><i class="fa-solid fa-file-pdf mr-1.5 text-xs"></i>Pratinjau PDF</a>
    <a href="{{ route('data-diri.biodata.unduh') }}" class="px-4 py-2 rounded-[10px] border border-slate-200 bg-white text-[13px] font-bold text-slate-700 no-underline hover:border-navy"><i class="fa-solid fa-download mr-1.5 text-xs"></i>Unduh PDF</a>
  </div>
  @unless($locked || $frozen)
  <div class="mb-5 rounded-[12px] border border-slate-200 bg-white px-4 py-3">@include('candidate-profile.partials.signed-upload')</div>
  @endunless

  @php
    $tabs = [
      1 => 'Identitas', 2 => 'Alamat', 3 => 'BPJS & Rekening', 4 => 'Data Keluarga',
      5 => 'Riwayat Pendidikan', 6 => 'Riwayat Pelatihan', 7 => 'Pengalaman Organisasi',
      8 => 'Pengalaman Kerja', 9 => 'Referensi', 10 => 'Informasi Tambahan',
      11 => 'Penilaian Diri', 12 => 'Dokumen',
    ];
    // Tahap biodata (sejak wawancara pertama): tanpa tab 3 (BPJS & Rekening) dan 12 (Dokumen).
    if (! $onboarding) { unset($tabs[3], $tabs[12]); }
    // Onboarding: BPJS & Rekening dan Dokumen (bisa diisi) di depan; tab biodata (hanya baca) di bawahnya.
    // Tab Dokumen (12) tidak dipakai: KTP/KK diunggah nanti di HRIS.
    unset($tabs[12]);
    if ($frozen) { $tabs = array_replace([3 => $tabs[3]], $tabs); } // hanya kalau biodata dibekukan
    $tabOrder = array_keys($tabs);
    $num = fn (int $n) => array_search($n, $tabOrder, true) + 1; // nomor tampil berurutan walau tab disembunyikan
    // Warna tab disamakan dengan warna border-top kartu section masing-masing.
    $tabColors = [
      1 => '#042c6c', 2 => '#0284c7', 3 => '#b45309', 4 => '#047C54',
      5 => '#B9308C', 6 => '#7c3aed', 7 => '#0f6e3d', 8 => '#042c6c',
      9 => '#0284c7', 10 => '#b45309', 11 => '#047C54', 12 => '#7c3aed',
    ];
  @endphp

  {{-- novalidate: validasi browser pada isian di tab yang tersembunyi memblokir submit tanpa pesan apa pun (tombol Simpan "tidak
       bisa diklik"). Validasi dilakukan server dan hasilnya tampil di banner di atas. --}}
  <form id="biodata-form" novalidate method="POST" action="{{ route('data-diri.update') }}" enctype="multipart/form-data" class="flex flex-col gap-4" x-data="{ tab: @js($tabOrder[0]), order: @js($tabOrder),
      step(d) { this.tab = this.order[Math.min(Math.max(this.order.indexOf(this.tab) + d, 0), this.order.length - 1)]; } }">
    @csrf
    @method('PATCH')

    @if($frozen)
    <div class="rounded-xl bg-sky-50 border border-sky-200 text-sky-800 text-[13px] font-semibold px-4 py-3">
      <i class="fa-solid fa-lock mr-1.5"></i>Biodata sudah final dan tidak bisa diubah (ada di bagian bawah, hanya untuk dilihat). Anda hanya perlu melengkapi BPJS dan No. Rekening.
    </div>
    @endif
    @if($locked)
    <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-[13px] font-semibold px-4 py-3">
      <i class="fa-solid fa-lock mr-1.5"></i>Biodata sudah dikunci karena lamaran Anda masuk tahap offering. Anda masih bisa melihat dan mencetak PDF-nya.
    </div>
    @endif

    {{-- Indikator kelengkapan data wajib --}}
    @php
      // Onboarding: biodata dibekukan, jadi hanya item tahap onboarding (BPJS/rekening/dokumen) yang bisa dikejar.
      $reqChecklist = $frozen ? array_filter($checklist, fn ($i) => $i['stage'] === 'onboarding') : $checklist;
      $reqFilled = collect($reqChecklist)->where('filled', true)->count();
      $reqTotal = count($reqChecklist);
      $reqDone = $reqFilled === $reqTotal;
      $reqPct = $reqTotal ? round($reqFilled / $reqTotal * 100) : 0;
    @endphp
    <div class="sg-card rounded-[16px] px-5 py-4" style="border-top:3px solid {{ $reqDone ? '#047C54' : '#b45309' }};">
      <div class="flex items-center justify-between gap-3 mb-2">
        <span class="text-[13.5px] font-extrabold text-slate-800">
          <i class="fa-solid {{ $reqDone ? 'fa-circle-check text-emerald-600' : 'fa-clipboard-list text-amber-600' }} mr-1.5"></i>
          Kelengkapan Data Wajib
        </span>
        <span class="text-[12px] font-bold tabular-nums {{ $reqDone ? 'text-emerald-600' : 'text-amber-600' }}">{{ $reqFilled }} / {{ $reqTotal }}</span>
      </div>
      <div class="h-1.5 rounded-full overflow-hidden mb-3" style="background:#e8edf5;">
        <div class="h-full rounded-full transition-all duration-300" style="width:{{ $reqPct }}%;background:{{ $reqDone ? '#047C54' : '#b45309' }};"></div>
      </div>
      @if($reqDone)
        <p class="m-0 text-[12px] text-emerald-700">Semua data wajib sudah lengkap. Terima kasih!</p>
      @else
        <p class="m-0 mb-2 text-[12px] text-slate-500">Masih ada data wajib yang belum diisi — klik untuk langsung ke bagiannya:</p>
        <div class="flex flex-wrap gap-1.5">
          @foreach($reqChecklist as $item)
            @unless($item['filled'])
            <button type="button" @click="tab = {{ $item['tab'] }}"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11.5px] font-semibold border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors">
              <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $item['label'] }}
            </button>
            @endunless
          @endforeach
        </div>
      @endif
    </div>

    {{-- Tab nav --}}
    <div class="flex flex-wrap gap-2">
      @foreach($tabs as $n => $label)
      @if($frozen && $n === 1)
      <div class="basis-full mt-1 text-[11.5px] font-bold uppercase tracking-wide text-slate-400">Biodata (hanya baca, tidak bisa diubah)</div>
      @endif
      <button type="button" @click="tab = {{ $n }}"
        class="px-4 py-2 rounded-[10px] text-[13px] font-bold whitespace-nowrap transition-colors duration-150 border"
        :class="tab === {{ $n }} ? '' : 'border-slate-200 text-slate-500 bg-white'"
        :style="tab === {{ $n }} ? 'background:{{ $tabColors[$n] }};border-color:{{ $tabColors[$n] }};color:#fff;' : ''">
        {{ $num($n) }}. {{ $label }}
      </button>
      @endforeach
    </div>

    {{-- 1. Identitas — isian dari katalog + master perusahaan (App\Support\BiodataFields) --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #042c6c;" x-show="tab === 1" x-cloak>
      <div class="flex gap-3 items-center mb-5">
        <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#042c6c;background:#042c6c1a;">{{ $num(1) }}</div>
        <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Identitas</h2>
      </div>
      <div class="sg-fields">
        @foreach($sections['identitas'] as $f) @include('candidate-profile.partials.field', ['f' => $f]) @endforeach
      </div>
    </section>

    {{-- 2. Alamat --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #0284c7;" x-show="tab === 2" x-cloak>
      <div class="flex gap-3 items-center mb-5">
        <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#0284c7;background:#0284c71a;">{{ $num(2) }}</div>
        <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Alamat Sesuai KTP</h2>
      </div>
      <div class="sg-fields">
        @foreach($sections['alamat'] as $f) @include('candidate-profile.partials.field', ['f' => $f]) @endforeach
      </div>
    </section>

    {{-- 3. BPJS & Rekening (tahap onboarding) --}}
    @if($onboarding)
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #b45309;" x-show="tab === 3" x-cloak>
      <div class="flex gap-3 items-center mb-5">
        <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#b45309;background:#b453091a;">{{ $num(3) }}</div>
        <h2 class="m-0 text-[17px] font-extrabold text-slate-800">BPJS &amp; Rekening</h2>
      </div>
      <div class="sg-fields">
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">No. BPJS Kesehatan</label>
          <input type="text" name="no_bpjskes" value="{{ old('no_bpjskes', $candidate->no_bpjskes) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Fasilitas Kesehatan</label>
          <input type="text" name="faskes" value="{{ old('faskes', $candidate->faskes) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">No. BPJS Ketenagakerjaan</label>
          <input type="text" name="no_bpjstk" value="{{ old('no_bpjstk', $candidate->no_bpjstk) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Perusahaan pada BPJS TK</label>
          <input type="text" name="company_bpjstk" value="{{ old('company_bpjstk', $candidate->company_bpjstk) }}" class="form-field" /></div>
        <div class="col-span-full"><label class="block mb-[7px] text-[13px] font-bold text-slate-700">No. Rekening BSI</label>
          <input type="text" name="bsi_bank_number" value="{{ old('bsi_bank_number', $candidate->bsi_bank_number) }}" class="form-field" /></div>
        {{-- Biodata sudah dibekukan: STR tidak bisa diubah, tapi scan-nya masih bisa diunggah di sini. --}}
        @if($frozen && $candidate->isHospital() && $candidate->jobApplication?->jobOpening?->is_nakes)
          @include('candidate-profile.partials.str-file')
        @endif
      </div>
    </section>

    @endif

    {{-- 4. Keluarga --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #047C54;"
      x-data='repeater(@json($familyMembersData))' x-show="tab === 4" x-cloak>
      <div class="flex gap-3 items-center mb-5 justify-between">
        <div class="flex gap-3 items-center">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#047C54;background:#047C541a;">{{ $num(4) }}</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Data Keluarga</h2>
        </div>
      </div>
      {{-- Desktop (md+): tabel. HP: daftar kartu dengan isian 2 kolom. Satu DOM saja (tidak ada isian ganda yang terkirim). --}}
      <div class="hidden md:grid md:grid-cols-[1.1fr_1.5fr_1.15fr_1.35fr_1fr_1fr_1.2fr_3.5rem] gap-2 px-1 pb-2 text-[12px] font-bold text-slate-500 border-b border-slate-200" x-show="rows.length > 0">
        <div>Hubungan</div><div>Nama</div><div>L/P</div><div>Tanggal Lahir</div><div>Pendidikan</div><div>Pekerjaan</div><div>No. HP</div><div></div>
      </div>
      <datalist id="relation-options"><option value="Ayah"><option value="Ibu"><option value="Saudara"><option value="Pasangan"><option value="Anak"></datalist>
      <template x-for="(row, i) in rows" :key="i">
        <div x-init="row.gender = famGender(row.relation) ?? row.gender" class="relative grid grid-cols-2 gap-x-3 gap-y-3 rounded-xl border border-slate-200 p-3 pt-10 mb-3
                    md:grid-cols-[1.1fr_1.5fr_1.15fr_1.35fr_1fr_1fr_1.2fr_3.5rem] md:gap-2 md:items-center md:rounded-none md:border-0 md:border-b md:p-1 md:px-1 md:pt-1 md:mb-0">
          <div><label class="md:hidden block mb-1 text-[12px] font-bold text-slate-600">Hubungan</label>
            <input type="text" list="relation-options" :name="'family['+i+'][relation]'" x-model="row.relation" @input="row.gender = famGender(row.relation) ?? row.gender" class="form-field fam-field" /></div>
          <div class="md:order-3"><label class="md:hidden block mb-1 text-[12px] font-bold text-slate-600">L/P</label>
            <select :name="'family['+i+'][gender]'" x-model="row.gender" class="form-field fam-field"
              :class="famGender(row.relation) ? 'pointer-events-none opacity-70' : ''" :tabindex="famGender(row.relation) ? -1 : 0">
              <option value="">Pilih...</option>
              <option value="Laki-laki">Laki-laki</option>
              <option value="Perempuan">Perempuan</option>
              <option :value="row.gender" x-show="row.gender && !['Laki-laki','Perempuan'].includes(row.gender)" x-text="row.gender"></option>
            </select></div>
          <div class="col-span-2 md:col-span-1 md:order-2"><label class="md:hidden block mb-1 text-[12px] font-bold text-slate-600">Nama</label>
            <input type="text" :name="'family['+i+'][name]'" x-model="row.name" class="form-field fam-field" /></div>
          <div class="md:order-4"><label class="md:hidden block mb-1 text-[12px] font-bold text-slate-600">Tanggal Lahir</label>
            <input type="date" :name="'family['+i+'][birth_date]'" x-model="row.birth_date" class="form-field fam-field" /></div>
          <div class="md:order-5"><label class="md:hidden block mb-1 text-[12px] font-bold text-slate-600">Pendidikan</label>
            <input type="text" :name="'family['+i+'][education]'" x-model="row.education" class="form-field fam-field" /></div>
          <div class="md:order-6"><label class="md:hidden block mb-1 text-[12px] font-bold text-slate-600">Pekerjaan</label>
            <input type="text" :name="'family['+i+'][job]'" x-model="row.job" class="form-field fam-field" /></div>
          <div class="md:order-7"><label class="md:hidden block mb-1 text-[12px] font-bold text-slate-600">No. HP</label>
            <input type="text" :name="'family['+i+'][phone]'" x-model="row.phone" class="form-field fam-field" /></div>
          <button type="button" @click="remove(i)" title="Hapus" aria-label="Hapus" class="absolute top-2 right-2 md:static md:order-8 md:justify-self-center grid place-items-center w-8 h-8 rounded-lg border-0 bg-transparent text-slate-400 hover:text-slate-600 hover:bg-slate-100 cursor-pointer"><i class="fa-solid fa-trash-can text-[13px]" aria-hidden="true"></i></button>
        </div>
      </template>
      <p x-show="rows.length === 0" class="text-[13px] text-slate-400">Belum ada data. Klik "+ Tambah" untuk menambahkan.</p>
      <button type="button" @click="add({relation:'',gender:'',name:'',birth_date:'',education:'',job:'',phone:''})" class="mt-3 flex w-full items-center justify-center gap-1.5 px-4 py-2.5 rounded-[10px] border border-dashed border-slate-300 text-[13px] font-bold text-navy hover:border-navy cursor-pointer bg-transparent">+ Tambah</button>
    </section>

    {{-- 5. Pendidikan --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #B9308C;"
      x-data='repeater(@json($educationsData))' x-show="tab === 5" x-cloak>
      <div class="flex gap-3 items-center mb-5 justify-between">
        <div class="flex gap-3 items-center">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#B9308C;background:#B9308C1a;">{{ $num(5) }}</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Riwayat Pendidikan</h2>
        </div>
      </div>
      @include('candidate-profile.partials.repeater-rows', ['prefix' => 'education', 'grid' => 'md:grid-cols-[1fr_2fr_1.5fr_.7fr_.8fr_.8fr_3.5rem]', 'fields' => [['key' => 'level', 'label' => 'Jenjang', 'm' => 1], ['key' => 'school_name', 'label' => 'Nama Sekolah/Universitas', 'span' => 'full', 'm' => 3, 'head' => 'Nama Sekolah/Universitas'], ['key' => 'major', 'label' => 'Jurusan', 'span' => 'full', 'm' => 4], ['key' => 'gpa', 'label' => 'IPK', 'type' => 'number', 'attrs' => 'step="0.01"', 'm' => 2], ['key' => 'start_year', 'label' => 'Tahun Masuk', 'type' => 'number', 'm' => 5], ['key' => 'end_year', 'label' => 'Tahun Lulus', 'type' => 'number', 'm' => 6]]])
      <p x-show="rows.length === 0" class="text-[13px] text-slate-400">Belum ada data.</p>
      <button type="button" @click="add({level:'',school_name:'',major:'',gpa:'',start_year:'',end_year:''})" class="mt-3 flex w-full items-center justify-center gap-1.5 px-4 py-2.5 rounded-[10px] border border-dashed border-slate-300 text-[13px] font-bold text-navy hover:border-navy cursor-pointer bg-transparent">+ Tambah</button>
    </section>

    {{-- 6. Pelatihan --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #7c3aed;"
      x-data='repeater(@json($trainingsData))' x-show="tab === 6" x-cloak>
      <div class="flex gap-3 items-center mb-5 justify-between">
        <div class="flex gap-3 items-center">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#7c3aed;background:#7c3aed1a;">{{ $num(6) }}</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Riwayat Pelatihan</h2>
        </div>
      </div>
      @include('candidate-profile.partials.repeater-rows', ['prefix' => 'training', 'grid' => 'md:grid-cols-[1fr_1.6fr_1.3fr_.9fr_1.2fr_3.5rem]', 'fields' => [['key' => 'type', 'label' => 'Jenis Pelatihan', 'm' => 1], ['key' => 'institution', 'label' => 'Penyelenggara', 'span' => 'full', 'm' => 3], ['key' => 'material', 'label' => 'Materi', 'span' => 'full', 'm' => 4], ['key' => 'duration', 'label' => 'Durasi', 'm' => 2], ['key' => 'location', 'label' => 'Lokasi', 'span' => 'full', 'm' => 5]]])
      <p x-show="rows.length === 0" class="text-[13px] text-slate-400">Belum ada data.</p>
      <button type="button" x-show="rows.length < 5" @click="rows.length < 5 && add({type:'',institution:'',material:'',duration:'',location:''})" class="mt-3 flex w-full items-center justify-center gap-1.5 px-4 py-2.5 rounded-[10px] border border-dashed border-slate-300 text-[13px] font-bold text-navy hover:border-navy cursor-pointer bg-transparent">+ Tambah</button>
      <p x-show="rows.length >= 5" class="mt-3 mb-0 text-[12.5px] text-slate-500">Maksimal 5 pelatihan (sesuai form biodata). Hapus salah satu untuk menambah yang lain.</p>
    </section>

    {{-- 7. Organisasi --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #0f6e3d;"
      x-data='repeater(@json($organizationsData))' x-show="tab === 7" x-cloak>
      <div class="flex gap-3 items-center mb-5 justify-between">
        <div class="flex gap-3 items-center">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#0f6e3d;background:#0f6e3d1a;">{{ $num(7) }}</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Pengalaman Organisasi</h2>
        </div>
      </div>
      @include('candidate-profile.partials.repeater-rows', ['prefix' => 'organization', 'grid' => 'md:grid-cols-[1.6fr_1.1fr_1.1fr_1fr_1.1fr_3.5rem]', 'fields' => [['key' => 'organization_name', 'label' => 'Nama Organisasi', 'span' => 'full', 'm' => 1], ['key' => 'field', 'label' => 'Bidang', 'm' => 2], ['key' => 'position', 'label' => 'Jabatan', 'm' => 3], ['key' => 'duration', 'label' => 'Periode', 'm' => 4], ['key' => 'location', 'label' => 'Lokasi', 'm' => 5]]])
      <p x-show="rows.length === 0" class="text-[13px] text-slate-400">Belum ada data.</p>
      <button type="button" x-show="rows.length < 5" @click="rows.length < 5 && add({organization_name:'',field:'',position:'',duration:'',location:''})" class="mt-3 flex w-full items-center justify-center gap-1.5 px-4 py-2.5 rounded-[10px] border border-dashed border-slate-300 text-[13px] font-bold text-navy hover:border-navy cursor-pointer bg-transparent">+ Tambah</button>
      <p x-show="rows.length >= 5" class="mt-3 mb-0 text-[12.5px] text-slate-500">Maksimal 5 organisasi (sesuai form biodata). Hapus salah satu untuk menambah yang lain.</p>
    </section>

    {{-- 8. Pengalaman Kerja --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #042c6c;"
      x-data='repeater(@json($workExperiencesData))' x-show="tab === 8" x-cloak>
      <div class="flex gap-3 items-center mb-5 justify-between">
        <div class="flex gap-3 items-center">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#042c6c;background:#042c6c1a;">{{ $num(8) }}</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Pengalaman Kerja</h2>
        </div>
      </div>
      @include('candidate-profile.partials.repeater-rows', ['prefix' => 'work', 'grid' => 'md:grid-cols-[1.4fr_1fr_1fr_1.25fr_1.25fr_1.2fr_1.2fr_3.5rem]', 'fields' => [['key' => 'company_name', 'label' => 'Nama Perusahaan', 'span' => 'full', 'm' => 1], ['key' => 'business_type', 'label' => 'Bidang Usaha', 'm' => 2], ['key' => 'position', 'label' => 'Jabatan', 'm' => 3], ['key' => 'salary_start', 'label' => 'Gaji Awal', 'type' => 'number', 'm' => 4], ['key' => 'salary', 'label' => 'Gaji Akhir (Terakhir)', 'type' => 'number', 'm' => 5, 'head' => 'Gaji Akhir'], ['key' => 'start_date', 'label' => 'Mulai', 'type' => 'date', 'm' => 6], ['key' => 'end_date', 'label' => 'Selesai', 'type' => 'date', 'm' => 7, 'attrs' => ':disabled="row.is_current"'], ['key' => 'company_address', 'label' => 'Alamat Perusahaan', 'extra' => true], ['key' => 'resign_reason', 'label' => 'Alasan Resign', 'extra' => true], ['key' => 'job_description', 'label' => 'Tugas & Tanggung Jawab (pekerjaan sekarang/terakhir)', 'extra' => true, 'hospital' => true, 'type' => 'textarea', 'show' => 'row.is_current'], ['key' => 'org_position_description', 'label' => 'Posisi Anda di Struktur Organisasi (pekerjaan sekarang/terakhir)', 'extra' => true, 'hospital' => true, 'type' => 'textarea', 'show' => 'row.is_current']]])
      <p x-show="rows.length === 0" class="text-[13px] text-slate-400">Belum ada data.</p>
      <button type="button" x-show="rows.length < 3" @click="rows.length < 3 && add({company_name:'',business_type:'',company_address:'',position:'',start_date:'',end_date:'',salary_start:'',salary:'',resign_reason:'',is_current:false,job_description:'',org_position_description:''})" class="mt-3 flex w-full items-center justify-center gap-1.5 px-4 py-2.5 rounded-[10px] border border-dashed border-slate-300 text-[13px] font-bold text-navy hover:border-navy cursor-pointer bg-transparent">+ Tambah</button>
      <p x-show="rows.length >= 3" class="mt-3 mb-0 text-[12.5px] text-slate-500">Maksimal 3 pekerjaan (sesuai form biodata). Hapus salah satu untuk menambah yang lain.</p>
    </section>

    {{-- 9. Referensi --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #0284c7;"
      x-data='repeater(@json($referencesData))' x-show="tab === 9" x-cloak>
      <div class="flex gap-3 items-center mb-5 justify-between">
        <div class="flex gap-3 items-center">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#0284c7;background:#0284c71a;">{{ $num(9) }}</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Referensi</h2>
        </div>
      </div>
      <p class="-mt-3 mb-4 text-[12px] font-semibold uppercase tracking-wide text-slate-500">Lampirkan no HRD/atasan tempat Anda bekerja sebelumnya</p>
      @include('candidate-profile.partials.repeater-rows', ['prefix' => 'reference', 'grid' => 'md:grid-cols-[1.4fr_1fr_1.1fr_1.1fr_2fr_3.5rem]', 'fields' => [['key' => 'name', 'label' => 'Nama', 'span' => 'full', 'm' => 1], ['key' => 'relation', 'label' => 'Hubungan', 'm' => 2], ['key' => 'job', 'label' => 'Pekerjaan', 'm' => 3], ['key' => 'phone', 'label' => 'No. HP', 'm' => 4], ['key' => 'address', 'label' => 'Alamat', 'span' => 'full', 'm' => 5]]])
      <p x-show="rows.length === 0" class="text-[13px] text-slate-400">Belum ada data.</p>
      <button type="button" x-show="rows.length < 3" @click="rows.length < 3 && add({name:'',address:'',job:'',phone:'',relation:''})" class="mt-3 flex w-full items-center justify-center gap-1.5 px-4 py-2.5 rounded-[10px] border border-dashed border-slate-300 text-[13px] font-bold text-navy hover:border-navy cursor-pointer bg-transparent">+ Tambah</button>
      <p x-show="rows.length >= 3" class="mt-3 mb-0 text-[12.5px] text-slate-500">Maksimal 3 referensi (sesuai form biodata). Hapus salah satu untuk menambah yang lain.</p>
    </section>

    {{-- 10. Info Tambahan --}}
    @php $addInfo = $candidate->additionalInfo; @endphp
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #b45309;" x-show="tab === 10" x-cloak>
      <div class="flex gap-3 items-center mb-5">
        <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#b45309;background:#b453091a;">{{ $num(10) }}</div>
        <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Informasi Tambahan</h2>
      </div>
      <div class="sg-fields">
        <label for="ainfo_ref" class="flex items-center gap-[10px] cursor-pointer">
          <div class="sg-chk relative w-[24px] h-[24px] shrink-0">
            <input type="checkbox" name="additional_info[allow_reference_check]" value="1" id="ainfo_ref" {{ old('additional_info.allow_reference_check', $addInfo?->allow_reference_check) ? 'checked' : '' }}
                   class="sg-chk-input absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10 m-0 p-0" />
            <span class="sg-chk-face flex items-center justify-center w-full h-full rounded-[5px] border-2 border-slate-300 transition-colors duration-150">
              <i class="fa-solid fa-check text-[12px] text-white opacity-0 transition-opacity duration-150 pointer-events-none"></i>
            </span>
          </div>
          <span class="text-[13px] text-slate-600">Bersedia referensi dihubungi</span>
        </label>
        <label for="ainfo_disease" class="flex items-center gap-[10px] cursor-pointer">
          <div class="sg-chk relative w-[24px] h-[24px] shrink-0">
            <input type="checkbox" name="additional_info[has_disease]" value="1" id="ainfo_disease" {{ old('additional_info.has_disease', $addInfo?->has_disease) ? 'checked' : '' }}
                   class="sg-chk-input absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10 m-0 p-0" />
            <span class="sg-chk-face flex items-center justify-center w-full h-full rounded-[5px] border-2 border-slate-300 transition-colors duration-150">
              <i class="fa-solid fa-check text-[12px] text-white opacity-0 transition-opacity duration-150 pointer-events-none"></i>
            </span>
          </div>
          <span class="text-[13px] text-slate-600">Memiliki catatan tentang penyakit yang pernah diderita atau sedang dirawat karena suatu penyakit (termasuk: ayan, hepatitis, tuli, vertigo, covid-19 dan lainnya)</span>
        </label>
        <div class="col-span-full"><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Catatan Penyakit (jika ada)</label>
          <textarea name="additional_info[disease_notes]" rows="2" class="form-field resize-y">{{ old('additional_info.disease_notes', $addInfo?->disease_notes) }}</textarea></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Hobi</label>
          <input type="text" name="additional_info[hobbies]" value="{{ old('additional_info.hobbies', $addInfo?->hobbies) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Bahasa yang Dikuasai</label>
          <input type="text" name="additional_info[languages]" value="{{ old('additional_info.languages', $addInfo?->languages) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Fasilitas Terakhir</label>
          <input type="text" name="additional_info[last_facilities]" value="{{ old('additional_info.last_facilities', $addInfo?->last_facilities) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Ekspektasi Gaji &amp; Fasilitas</label>
          <input type="text" name="additional_info[expected_salary_facilities]" value="{{ old('additional_info.expected_salary_facilities', $addInfo?->expected_salary_facilities) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Tersedia Mulai Bekerja</label>
          <input type="date" name="additional_info[available_start_date]" value="{{ old('additional_info.available_start_date', $addInfo?->available_start_date?->format('Y-m-d')) }}" class="form-field" /></div>
        @foreach($sections['informasi_tambahan'] as $f) @include('candidate-profile.partials.field', ['f' => $f]) @endforeach
      </div>
    </section>

    {{-- 11. Penilaian Diri --}}
    @php $selfAssess = $candidate->selfAssessment; @endphp
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #047C54;" x-show="tab === 11" x-cloak>
      <div class="flex gap-3 items-center mb-5">
        <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#047C54;background:#047C541a;">{{ $num(11) }}</div>
        <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Penilaian Diri</h2>
      </div>
      <div class="sg-fields">
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Kelebihan</label>
          <textarea name="self_assessment[strengths]" rows="2" class="form-field resize-y">{{ old('self_assessment.strengths', $selfAssess?->strengths) }}</textarea></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Kekurangan</label>
          <textarea name="self_assessment[weaknesses]" rows="2" class="form-field resize-y">{{ old('self_assessment.weaknesses', $selfAssess?->weaknesses) }}</textarea></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Tingkat Kepuasan Karier Saat Ini</label>
          <select name="self_assessment[satisfaction_level]" class="form-field">
            <option value="">Pilih...</option>
            @foreach(['very_unsatisfied'=>'Sangat Tidak Puas','unsatisfied'=>'Tidak Puas','less_satisfied'=>'Kurang Puas','satisfied'=>'Puas','very_satisfied'=>'Sangat Puas'] as $val => $label)
              <option value="{{ $val }}" {{ old('self_assessment.satisfaction_level', $selfAssess?->satisfaction_level) === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-span-full"><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Alasan</label>
          <textarea name="self_assessment[reason]" rows="2" class="form-field resize-y">{{ old('self_assessment.reason', $selfAssess?->reason) }}</textarea></div>
        @foreach($sections['penilaian_diri'] as $f) @include('candidate-profile.partials.field', ['f' => $f]) @endforeach
      </div>
    </section>

    {{-- 12. Dokumen (tahap onboarding) --}}
    @if($onboarding)
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #7c3aed;" x-show="tab === 12" x-cloak>
      <div class="flex gap-3 items-center mb-5">
        <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#7c3aed;background:#7c3aed1a;">{{ $num(12) }}</div>
        <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Dokumen</h2>
      </div>

      @if($candidate->documents->isNotEmpty())
      <div class="mb-4 flex flex-col gap-2">
        @foreach($candidate->documents as $doc)
        <a href="{{ route('data-diri.dokumen', $doc) }}" class="flex items-center gap-2 text-[13px] text-navy no-underline hover:underline">
          <i class="fa-solid fa-file-lines"></i> {{ $doc->document_label ?? $doc->doc_type }}
        </a>
        @endforeach
      </div>
      @endif

      <div x-data="repeater([])">
        <template x-for="(row, i) in rows" :key="i">
          <div class="relative pt-9 sg-fields sg-dd-border mb-4 pb-4" style="border-bottom-width:1px;border-bottom-style:solid;">
          <button type="button" @click="remove(i)" title="Hapus" aria-label="Hapus" class="absolute top-0 right-0 grid place-items-center w-8 h-8 rounded-lg border-0 bg-transparent text-slate-400 hover:text-slate-600 hover:bg-slate-100 cursor-pointer"><i class="fa-solid fa-trash-can text-[13px]" aria-hidden="true"></i></button>
            <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Jenis Dokumen</label>
              <input type="text" :name="'documents['+i+'][doc_type]'" x-model="row.doc_type" placeholder="ktp, kk, ijazah, bpjstk, dll" class="form-field" /></div>
            <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Label</label>
              <input type="text" :name="'documents['+i+'][label]'" x-model="row.label" class="form-field" /></div>
            <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">File</label>
              <input type="file" :name="'documents['+i+'][file]'" class="form-field" /></div>
          </div>
        </template>
        <button type="button" @click="add({doc_type:'',label:''})" class="mt-3 flex w-full items-center justify-center gap-1.5 px-4 py-2.5 rounded-[10px] border border-dashed border-slate-300 text-[13px] font-bold text-navy hover:border-navy cursor-pointer bg-transparent">+ Tambah Dokumen</button>
      </div>
    </section>

    @endif

    {{-- Pernyataan: di luar kartu tab, di atas tombol navigasi, supaya terlihat di semua tab. --}}
    <label class="flex items-start gap-3 cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-3">
      <input type="checkbox" name="biodata_confirmed" value="1" class="mt-0.5 rounded border-slate-300" {{ old('biodata_confirmed', $candidate->biodata_confirmed_at) ? 'checked' : '' }}>
      <span class="text-[13px] text-slate-600">Dengan ini saya menyatakan bahwa informasi yang saya isikan di atas adalah benar. Apabila ditemukan bahwa informasi tidak benar, perusahaan berhak membatalkan proses rekrutmen atau melakukan pemutusan hubungan kerja.</span>
    </label>

    <div class="flex justify-between items-center flex-wrap gap-3">
      <div class="flex gap-2">
        <button type="button" @click="step(-1)" :disabled="tab === order[0]"
          class="px-4 py-[10px] rounded-xl border border-slate-200 text-slate-600 text-[13px] font-bold disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer bg-white">
          ← Sebelumnya
        </button>
        <button type="button" @click="step(1)" :disabled="tab === order[order.length - 1]"
          class="px-4 py-[10px] rounded-xl border-0 bg-navy hover:bg-navy-dark text-white text-[13px] font-bold disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">
          Selanjutnya →
        </button>
      </div>
      @unless($locked)
      <button type="submit" class="px-[26px] py-3 rounded-xl border-0 bg-sgg-green hover:bg-sgg-green-hover text-white text-[13.5px] font-bold cursor-pointer">
        <i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Data Diri
      </button>
      @endunless
    </div>

  </form>
  @if($locked || $frozen)
  {{-- Read-only: isian tab tidak bisa difokus/diklik (tombol tab dan Sebelumnya/Selanjutnya tetap jalan). Saat onboarding
       tab BPJS & Rekening (3) tetap bisa diisi. --}}
  <script>document.querySelectorAll('section[x-show]').forEach(function (s) {
    if (@json((bool) $frozen) && /tab === 3\b/.test(s.getAttribute('x-show'))) return;
    s.inert = true; s.style.opacity = '.85';
  });</script>
  @endif

</div>

<script>
// Ayah selalu Laki-laki, Ibu selalu Perempuan; hubungan lain bebas (null).
window.famGender = (relation) => ({ ayah: 'Laki-laki', bapak: 'Laki-laki', ibu: 'Perempuan' })[(relation || '').trim().toLowerCase()] ?? null;
document.addEventListener('alpine:init', () => {
  Alpine.data('repeater', (initial = []) => ({
    rows: initial.length ? initial : [],
    add(row) { this.rows.push(row); },
    remove(i) { this.rows.splice(i, 1); },
  }));
});
</script>
</x-app-layout>
