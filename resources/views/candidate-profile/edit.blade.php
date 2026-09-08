<x-app-layout>
<x-slot name="title">Data Diri Kandidat — Syifa Global Group</x-slot>

@php
// ponytail: @json() Blade directive does a naive explode(',', ...) on its argument,
// so an inline array literal with 4+ items gets silently truncated. Precompute into
// plain variables here so each @json() call below has zero top-level commas.
$familyMembersData  = $candidate->familyMembers->map->only(['relation','gender','name','birth_date','education','job','phone']);
$educationsData     = $candidate->educations->map->only(['level','school_name','major','gpa','start_year','end_year']);
$trainingsData      = $candidate->trainings->map->only(['type','institution','material','duration','location']);
$organizationsData  = $candidate->organizations->map->only(['organization_name','field','position','duration','location']);
$workExperiencesData = $candidate->workExperiences->map->only(['company_name','business_type','company_address','position','start_date','end_date','salary','resign_reason','is_current']);
$referencesData     = $candidate->references->map->only(['name','address','job','phone','relation']);
@endphp

<div>

  @if(session('success'))
  <div class="mb-4 rounded-xl px-4 py-3 border border-green-200 bg-green-600/[0.07] text-sm font-semibold text-green-700">{{ session('success') }}</div>
  @endif

  @if($errors->any())
  <div class="mb-4 rounded-xl px-4 py-3 border border-red-200 bg-red-600/[0.06]">
    <div class="text-sm font-bold text-red-700 mb-1">Periksa kembali isian kamu:</div>
    <ul class="text-[12.5px] text-red-600 list-disc pl-4">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
  @endif

  <h1 class="m-0 mb-1 text-[22px] font-extrabold text-slate-800">Data Diri Kandidat</h1>
  <p class="m-0 mb-5 text-[13.5px] text-slate-500">Lengkapi data ini untuk meneyelesaikan proses rekrutmen — identitas resmi, keluarga, pendidikan, dan dokumen pendukung.</p>

  @php
    $tabs = [
      1 => 'Identitas', 2 => 'Alamat', 3 => 'BPJS & Rekening', 4 => 'Data Keluarga',
      5 => 'Riwayat Pendidikan', 6 => 'Riwayat Pelatihan', 7 => 'Pengalaman Organisasi',
      8 => 'Pengalaman Kerja', 9 => 'Referensi', 10 => 'Informasi Tambahan',
      11 => 'Penilaian Diri', 12 => 'Dokumen',
    ];
    // Warna tab disamakan dengan warna border-top kartu section masing-masing.
    $tabColors = [
      1 => '#042c6c', 2 => '#0284c7', 3 => '#b45309', 4 => '#047C54',
      5 => '#B9308C', 6 => '#7c3aed', 7 => '#0f6e3d', 8 => '#042c6c',
      9 => '#0284c7', 10 => '#b45309', 11 => '#047C54', 12 => '#7c3aed',
    ];
  @endphp

  <form method="POST" action="{{ route('data-diri.update') }}" enctype="multipart/form-data" class="flex flex-col gap-4" x-data="{ tab: 1 }">
    @csrf
    @method('PATCH')

    {{-- Indikator kelengkapan data wajib --}}
    @php
      $reqChecklist = $candidate->requiredDataChecklist();
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
      <button type="button" @click="tab = {{ $n }}"
        class="px-4 py-2 rounded-[10px] text-[13px] font-bold whitespace-nowrap transition-colors duration-150 border"
        :class="tab === {{ $n }} ? '' : 'border-slate-200 text-slate-500 bg-white'"
        :style="tab === {{ $n }} ? 'background:{{ $tabColors[$n] }};border-color:{{ $tabColors[$n] }};color:#fff;' : ''">
        {{ $n }}. {{ $label }}
      </button>
      @endforeach
    </div>

    {{-- 1. Identitas --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #042c6c;" x-show="tab === 1" x-cloak>
      <div class="flex gap-3 items-center mb-5">
        <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#042c6c;background:#042c6c1a;">1</div>
        <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Identitas</h2>
      </div>
      <div class="sg-fields">
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Nama Lengkap *</label>
          <input type="text" name="name" value="{{ old('name', $candidate->name) }}" required class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Nama Panggilan</label>
          <input type="text" name="nickname" value="{{ old('nickname', $candidate->nickname) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">NIK</label>
          <input type="text" name="nik" value="{{ old('nik', $candidate->nik) }}" maxlength="16" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">No. Kartu Keluarga</label>
          <input type="text" name="no_kk" value="{{ old('no_kk', $candidate->no_kk) }}" maxlength="16" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Golongan Darah</label>
          <input type="text" name="blood_type" value="{{ old('blood_type', $candidate->blood_type) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Tinggi/Berat Badan</label>
          <input type="text" name="body_height_weight" placeholder="Contoh: 165cm / 55kg" value="{{ old('body_height_weight', $candidate->body_height_weight) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Agama</label>
          <input type="text" name="religion" value="{{ old('religion', $candidate->religion) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Kewarganegaraan</label>
          <input type="text" name="nationality" value="{{ old('nationality', $candidate->nationality ?? 'Indonesia') }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Status Pernikahan</label>
          <input type="text" name="status_marital" value="{{ old('status_marital', $candidate->status_marital) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Tanggal Menikah</label>
          <input type="date" name="marriage_date" value="{{ old('marriage_date', $candidate->marriage_date?->format('Y-m-d')) }}" class="form-field" /></div>
        <div class="col-span-full"><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Nama Ibu Kandung</label>
          <input type="text" name="mother_name" value="{{ old('mother_name', $candidate->mother_name) }}" class="form-field" /></div>
      </div>
    </section>

    {{-- 2. Alamat --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #0284c7;" x-show="tab === 2" x-cloak>
      <div class="flex gap-3 items-center mb-5">
        <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#0284c7;background:#0284c71a;">2</div>
        <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Alamat Sesuai KTP</h2>
      </div>
      <div class="sg-fields">
        <div class="col-span-full"><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Alamat</label>
          <textarea name="ktp_address" rows="2" class="form-field resize-y">{{ old('ktp_address', $candidate->ktp_address) }}</textarea></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Kelurahan/Desa</label>
          <input type="text" name="village" value="{{ old('village', $candidate->village) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Kecamatan</label>
          <input type="text" name="district" value="{{ old('district', $candidate->district) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Kota/Kabupaten</label>
          <input type="text" name="city" value="{{ old('city', $candidate->city) }}" class="form-field" /></div>
        <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Provinsi</label>
          <input type="text" name="province" value="{{ old('province', $candidate->province) }}" class="form-field" /></div>
        <div class="col-span-full"><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Akun Media Sosial</label>
          <input type="text" name="social_media_accounts" value="{{ old('social_media_accounts', $candidate->social_media_accounts) }}" class="form-field" /></div>
      </div>
    </section>

    {{-- 3. BPJS & Rekening --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #b45309;" x-show="tab === 3" x-cloak>
      <div class="flex gap-3 items-center mb-5">
        <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#b45309;background:#b453091a;">3</div>
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
      </div>
    </section>

    {{-- 4. Keluarga --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #047C54;"
      x-data="repeater(@json($familyMembersData))" x-show="tab === 4" x-cloak>
      <div class="flex gap-3 items-center mb-5 justify-between">
        <div class="flex gap-3 items-center">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#047C54;background:#047C541a;">4</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Data Keluarga</h2>
        </div>
        <button type="button" @click="add({relation:'',gender:'',name:'',birth_date:'',education:'',job:'',phone:''})" class="text-[12.5px] font-bold text-navy">+ Tambah</button>
      </div>
      <template x-for="(row, i) in rows" :key="i">
        <div class="sg-fields sg-dd-border mb-4 pb-4" style="border-bottom-width:1px;border-bottom-style:solid;">
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Hubungan</label>
            <input type="text" :name="'family['+i+'][relation]'" x-model="row.relation" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Nama</label>
            <input type="text" :name="'family['+i+'][name]'" x-model="row.name" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Jenis Kelamin</label>
            <input type="text" :name="'family['+i+'][gender]'" x-model="row.gender" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Tanggal Lahir</label>
            <input type="date" :name="'family['+i+'][birth_date]'" x-model="row.birth_date" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Pendidikan</label>
            <input type="text" :name="'family['+i+'][education]'" x-model="row.education" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Pekerjaan</label>
            <input type="text" :name="'family['+i+'][job]'" x-model="row.job" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">No. HP</label>
            <input type="text" :name="'family['+i+'][phone]'" x-model="row.phone" class="form-field" /></div>
          <div class="flex items-end"><button type="button" @click="remove(i)" class="text-[12.5px] font-bold text-red-600">Hapus</button></div>
        </div>
      </template>
      <p x-show="rows.length === 0" class="text-[13px] text-slate-400">Belum ada data. Klik "+ Tambah" untuk menambahkan.</p>
    </section>

    {{-- 5. Pendidikan --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #B9308C;"
      x-data="repeater(@json($educationsData))" x-show="tab === 5" x-cloak>
      <div class="flex gap-3 items-center mb-5 justify-between">
        <div class="flex gap-3 items-center">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#B9308C;background:#B9308C1a;">5</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Riwayat Pendidikan</h2>
        </div>
        <button type="button" @click="add({level:'',school_name:'',major:'',gpa:'',start_year:'',end_year:''})" class="text-[12.5px] font-bold text-navy">+ Tambah</button>
      </div>
      <template x-for="(row, i) in rows" :key="i">
        <div class="sg-fields sg-dd-border mb-4 pb-4" style="border-bottom-width:1px;border-bottom-style:solid;">
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Jenjang</label>
            <input type="text" :name="'education['+i+'][level]'" x-model="row.level" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Nama Sekolah/Universitas</label>
            <input type="text" :name="'education['+i+'][school_name]'" x-model="row.school_name" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Jurusan</label>
            <input type="text" :name="'education['+i+'][major]'" x-model="row.major" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">IPK</label>
            <input type="number" step="0.01" :name="'education['+i+'][gpa]'" x-model="row.gpa" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Tahun Masuk</label>
            <input type="number" :name="'education['+i+'][start_year]'" x-model="row.start_year" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Tahun Lulus</label>
            <input type="number" :name="'education['+i+'][end_year]'" x-model="row.end_year" class="form-field" /></div>
          <div class="flex items-end"><button type="button" @click="remove(i)" class="text-[12.5px] font-bold text-red-600">Hapus</button></div>
        </div>
      </template>
      <p x-show="rows.length === 0" class="text-[13px] text-slate-400">Belum ada data.</p>
    </section>

    {{-- 6. Pelatihan --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #7c3aed;"
      x-data="repeater(@json($trainingsData))" x-show="tab === 6" x-cloak>
      <div class="flex gap-3 items-center mb-5 justify-between">
        <div class="flex gap-3 items-center">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#7c3aed;background:#7c3aed1a;">6</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Riwayat Pelatihan</h2>
        </div>
        <button type="button" @click="add({type:'',institution:'',material:'',duration:'',location:''})" class="text-[12.5px] font-bold text-navy">+ Tambah</button>
      </div>
      <template x-for="(row, i) in rows" :key="i">
        <div class="sg-fields sg-dd-border mb-4 pb-4" style="border-bottom-width:1px;border-bottom-style:solid;">
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Jenis Pelatihan</label>
            <input type="text" :name="'training['+i+'][type]'" x-model="row.type" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Penyelenggara</label>
            <input type="text" :name="'training['+i+'][institution]'" x-model="row.institution" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Materi</label>
            <input type="text" :name="'training['+i+'][material]'" x-model="row.material" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Durasi</label>
            <input type="text" :name="'training['+i+'][duration]'" x-model="row.duration" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Lokasi</label>
            <input type="text" :name="'training['+i+'][location]'" x-model="row.location" class="form-field" /></div>
          <div class="flex items-end"><button type="button" @click="remove(i)" class="text-[12.5px] font-bold text-red-600">Hapus</button></div>
        </div>
      </template>
      <p x-show="rows.length === 0" class="text-[13px] text-slate-400">Belum ada data.</p>
    </section>

    {{-- 7. Organisasi --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #0f6e3d;"
      x-data="repeater(@json($organizationsData))" x-show="tab === 7" x-cloak>
      <div class="flex gap-3 items-center mb-5 justify-between">
        <div class="flex gap-3 items-center">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#0f6e3d;background:#0f6e3d1a;">7</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Pengalaman Organisasi</h2>
        </div>
        <button type="button" @click="add({organization_name:'',field:'',position:'',duration:'',location:''})" class="text-[12.5px] font-bold text-navy">+ Tambah</button>
      </div>
      <template x-for="(row, i) in rows" :key="i">
        <div class="sg-fields sg-dd-border mb-4 pb-4" style="border-bottom-width:1px;border-bottom-style:solid;">
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Nama Organisasi</label>
            <input type="text" :name="'organization['+i+'][organization_name]'" x-model="row.organization_name" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Bidang</label>
            <input type="text" :name="'organization['+i+'][field]'" x-model="row.field" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Jabatan</label>
            <input type="text" :name="'organization['+i+'][position]'" x-model="row.position" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Periode</label>
            <input type="text" :name="'organization['+i+'][duration]'" x-model="row.duration" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Lokasi</label>
            <input type="text" :name="'organization['+i+'][location]'" x-model="row.location" class="form-field" /></div>
          <div class="flex items-end"><button type="button" @click="remove(i)" class="text-[12.5px] font-bold text-red-600">Hapus</button></div>
        </div>
      </template>
      <p x-show="rows.length === 0" class="text-[13px] text-slate-400">Belum ada data.</p>
    </section>

    {{-- 8. Pengalaman Kerja --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #042c6c;"
      x-data="repeater(@json($workExperiencesData))" x-show="tab === 8" x-cloak>
      <div class="flex gap-3 items-center mb-5 justify-between">
        <div class="flex gap-3 items-center">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#042c6c;background:#042c6c1a;">8</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Pengalaman Kerja</h2>
        </div>
        <button type="button" @click="add({company_name:'',business_type:'',company_address:'',position:'',start_date:'',end_date:'',salary:'',resign_reason:'',is_current:false})" class="text-[12.5px] font-bold text-navy">+ Tambah</button>
      </div>
      <template x-for="(row, i) in rows" :key="i">
        <div class="sg-fields sg-dd-border mb-4 pb-4" style="border-bottom-width:1px;border-bottom-style:solid;">
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Nama Perusahaan</label>
            <input type="text" :name="'work['+i+'][company_name]'" x-model="row.company_name" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Bidang Usaha</label>
            <input type="text" :name="'work['+i+'][business_type]'" x-model="row.business_type" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Jabatan</label>
            <input type="text" :name="'work['+i+'][position]'" x-model="row.position" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Gaji Terakhir</label>
            <input type="number" :name="'work['+i+'][salary]'" x-model="row.salary" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Mulai</label>
            <input type="date" :name="'work['+i+'][start_date]'" x-model="row.start_date" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Selesai</label>
            <input type="date" :name="'work['+i+'][end_date]'" x-model="row.end_date" :disabled="row.is_current" class="form-field" /></div>
          <div class="col-span-full"><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Alamat Perusahaan</label>
            <input type="text" :name="'work['+i+'][company_address]'" x-model="row.company_address" class="form-field" /></div>
          <div class="col-span-full"><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Alasan Resign</label>
            <input type="text" :name="'work['+i+'][resign_reason]'" x-model="row.resign_reason" class="form-field" /></div>
          <div class="flex items-center gap-2">
            <input type="hidden" :name="'work['+i+'][is_current]'" :value="row.is_current ? 1 : 0" />
            <input type="checkbox" x-model="row.is_current" :id="'cur'+i" />
            <label :for="'cur'+i" class="text-[13px] text-slate-600">Masih bekerja di sini</label>
          </div>
          <div class="flex items-end"><button type="button" @click="remove(i)" class="text-[12.5px] font-bold text-red-600">Hapus</button></div>
        </div>
      </template>
      <p x-show="rows.length === 0" class="text-[13px] text-slate-400">Belum ada data.</p>
    </section>

    {{-- 9. Referensi --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #0284c7;"
      x-data="repeater(@json($referencesData))" x-show="tab === 9" x-cloak>
      <div class="flex gap-3 items-center mb-5 justify-between">
        <div class="flex gap-3 items-center">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#0284c7;background:#0284c71a;">9</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800">Referensi</h2>
        </div>
        <button type="button" @click="add({name:'',address:'',job:'',phone:'',relation:''})" class="text-[12.5px] font-bold text-navy">+ Tambah</button>
      </div>
      <template x-for="(row, i) in rows" :key="i">
        <div class="sg-fields sg-dd-border mb-4 pb-4" style="border-bottom-width:1px;border-bottom-style:solid;">
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Nama</label>
            <input type="text" :name="'reference['+i+'][name]'" x-model="row.name" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Hubungan</label>
            <input type="text" :name="'reference['+i+'][relation]'" x-model="row.relation" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Pekerjaan</label>
            <input type="text" :name="'reference['+i+'][job]'" x-model="row.job" class="form-field" /></div>
          <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">No. HP</label>
            <input type="text" :name="'reference['+i+'][phone]'" x-model="row.phone" class="form-field" /></div>
          <div class="col-span-full"><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Alamat</label>
            <input type="text" :name="'reference['+i+'][address]'" x-model="row.address" class="form-field" /></div>
          <div class="flex items-end"><button type="button" @click="remove(i)" class="text-[12.5px] font-bold text-red-600">Hapus</button></div>
        </div>
      </template>
      <p x-show="rows.length === 0" class="text-[13px] text-slate-400">Belum ada data.</p>
    </section>

    {{-- 10. Info Tambahan --}}
    @php $addInfo = $candidate->additionalInfo; @endphp
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #b45309;" x-show="tab === 10" x-cloak>
      <div class="flex gap-3 items-center mb-5">
        <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#b45309;background:#b453091a;">10</div>
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
          <span class="text-[13px] text-slate-600">Memiliki riwayat penyakit</span>
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
      </div>
    </section>

    {{-- 11. Penilaian Diri --}}
    @php $selfAssess = $candidate->selfAssessment; @endphp
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #047C54;" x-show="tab === 11" x-cloak>
      <div class="flex gap-3 items-center mb-5">
        <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#047C54;background:#047C541a;">11</div>
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
      </div>
    </section>

    {{-- 12. Dokumen --}}
    <section class="sg-card rounded-[20px] py-[26px] px-7" style="border-top:3px solid #7c3aed;" x-show="tab === 12" x-cloak>
      <div class="flex gap-3 items-center mb-5">
        <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#7c3aed;background:#7c3aed1a;">12</div>
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
        <button type="button" @click="add({doc_type:'',label:''})" class="mb-3 text-[12.5px] font-bold text-navy">+ Tambah Dokumen</button>
        <template x-for="(row, i) in rows" :key="i">
          <div class="sg-fields sg-dd-border mb-4 pb-4" style="border-bottom-width:1px;border-bottom-style:solid;">
            <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Jenis Dokumen</label>
              <input type="text" :name="'documents['+i+'][doc_type]'" x-model="row.doc_type" placeholder="ktp, kk, ijazah, bpjstk, dll" class="form-field" /></div>
            <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">Label</label>
              <input type="text" :name="'documents['+i+'][label]'" x-model="row.label" class="form-field" /></div>
            <div><label class="block mb-[7px] text-[13px] font-bold text-slate-700">File</label>
              <input type="file" :name="'documents['+i+'][file]'" class="form-field" /></div>
            <div class="flex items-end"><button type="button" @click="remove(i)" class="text-[12.5px] font-bold text-red-600">Hapus</button></div>
          </div>
        </template>
      </div>
    </section>

    <div class="flex justify-between items-center flex-wrap gap-3">
      <div class="flex gap-2">
        <button type="button" @click="tab = Math.max(tab - 1, 1)" :disabled="tab === 1"
          class="px-4 py-[10px] rounded-xl border border-slate-200 text-slate-600 text-[13px] font-bold disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer bg-white">
          ← Sebelumnya
        </button>
        <button type="button" @click="tab = Math.min(tab + 1, {{ count($tabs) }})" :disabled="tab === {{ count($tabs) }}"
          class="px-4 py-[10px] rounded-xl border-0 bg-navy hover:bg-navy-dark text-white text-[13px] font-bold disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">
          Selanjutnya →
        </button>
      </div>
      <button type="submit" class="px-[26px] py-3 rounded-xl border-0 bg-sgg-green hover:bg-sgg-green-hover text-white text-[13.5px] font-bold cursor-pointer">
        <i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Data Diri
      </button>
    </div>

  </form>

</div>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('repeater', (initial = []) => ({
    rows: initial.length ? initial : [],
    add(row) { this.rows.push(row); },
    remove(i) { this.rows.splice(i, 1); },
  }));
});
</script>
</x-app-layout>
