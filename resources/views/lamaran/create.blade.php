<x-public-layout>
<x-slot name="title">Formulir Lamaran — {{ $jobOpening->title }}</x-slot>

<div class="sg-rise">
  {{-- Top gradient bar --}}
  <div class="h-1" style="background:linear-gradient(90deg,#042c6c 0%,#0b47a1 55%,#B9308C 100%);"></div>

  {{-- Header --}}
  <header class="bg-white border-b border-slate-200">
    <div class="max-w-[1000px] mx-auto px-6 py-[14px] flex items-center gap-4">
      <a href="{{ route('welcome') }}" class="w-[38px] h-[38px] shrink-0 rounded-[11px] border border-slate-200 bg-white text-slate-600 grid place-items-center no-underline hover:border-slate-300 transition-colors duration-150">
        <i class="fa-solid fa-arrow-left"></i>
      </a>
      <div class="leading-[1.2]">
        <div class="text-[11.5px] font-bold tracking-[0.07em] uppercase text-slate-500">
          {{ now()->translatedFormat('l, j F Y') }} · {{ now()->format('H:i') }} WIB
        </div>
        <div class="text-[17px] font-extrabold text-slate-800 tracking-[-0.3px]">Formulir Lamaran</div>
      </div>
      <div class="ml-auto flex items-center gap-[10px]">
        <div class="w-[34px] h-[34px] rounded-full grid place-items-center text-[13px] font-extrabold text-navy bg-navy/[0.09]">
          {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
        </div>
      </div>
    </div>
  </header>

  {{-- Main --}}
  <main class="max-w-[1000px] mx-auto px-5 pt-7 pb-24">

    {{-- Position card --}}
    <div class="relative overflow-hidden rounded-[20px] text-white px-[30px] py-7" style="background:linear-gradient(135deg,#042c6c 0%,#0b47a1 100%);">
      <div class="absolute w-[230px] h-[230px] rounded-full -right-[70px] -top-[110px] pointer-events-none bg-white/[0.07]"></div>
      <div class="absolute w-[130px] h-[130px] rounded-[30px] rotate-[22deg] right-[170px] -bottom-[80px] pointer-events-none bg-magenta/[0.18]"></div>
      <div class="relative z-[1] flex flex-wrap gap-[18px] items-center justify-between">
        <div>
          <div class="text-[11.5px] font-bold tracking-[0.08em] uppercase opacity-[0.82]">Melamar untuk posisi</div>
          <h1 class="mt-2 mb-1.5 text-2xl font-extrabold tracking-[-0.5px]">{{ $jobOpening->title }}</h1>
          <div class="text-[13.5px] text-white/[0.82]">{{ $jobOpening->company_name }} · Kuota: {{ $jobOpening->quota }} orang</div>
        </div>
      </div>
    </div>

    {{-- Flash errors --}}
    @if($errors->any())
    <div class="mt-4 rounded-[14px] px-5 py-4 border border-red-200 bg-red-600/[0.07]">
      <div class="text-[13.5px] font-bold text-red-600 mb-2"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>Perbaiki isian berikut:</div>
      <ul class="m-0 pl-[18px] text-[13px] text-red-600 flex flex-col gap-1">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
    </div>
    @endif

    {{-- Banner: gunakan data lamaran sebelumnya --}}
    @if(isset($previousApp) && $previousApp)
    <div class="mt-4 rounded-[14px] px-5 py-4 border flex flex-wrap items-center gap-3" style="background:rgba(4,44,108,0.04);border-color:rgba(4,44,108,0.18);">
      <div class="flex items-center gap-3 flex-1 min-w-0">
        <div class="w-9 h-9 shrink-0 rounded-[10px] grid place-items-center" style="background:rgba(4,44,108,0.1);">
          <i class="fa-solid fa-rotate-left text-navy" style="font-size:14px;"></i>
        </div>
        <div>
          <div class="text-[13.5px] font-bold text-slate-800">Pernah melamar sebelumnya?</div>
          <div class="text-[12.5px] text-slate-500 mt-0.5">Data diri dari lamaran <strong>{{ $previousApp->jobOpening->title ?? '' }}</strong> dapat digunakan kembali — kecuali ekspektasi, ketersediaan, dan dokumen.</div>
        </div>
      </div>
      <button type="button" onclick="fillFromPrevious()" class="shrink-0 px-4 py-2 rounded-[9px] text-[13px] font-bold text-white transition-colors duration-150" style="background:#042c6c;">
        <i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i>Gunakan Data Sebelumnya
      </button>
    </div>
    @endif

    @php
    $prevData = $previousApp ? [
        'full_name'            => $previousApp->full_name,
        'gender'               => $previousApp->gender,
        'birth_place'          => $previousApp->birth_place,
        'birth_date'           => $previousApp->birth_date?->format('Y-m-d'),
        'domicile_address'     => $previousApp->domicile_address,
        'whatsapp_number'      => $previousApp->whatsapp_number,
        'instagram_handle'     => $previousApp->instagram_handle,
        'linkedin_url'         => $previousApp->linkedin_url,
        'education_level'      => $previousApp->education_level,
        'major'                => $previousApp->major,
        'school_name'          => $previousApp->school_name,
        'graduation_year'      => $previousApp->graduation_year,
        'str_valid_until'      => $previousApp->str_valid_until?->format('Y-m-d'),
        'other_position_note'  => $previousApp->other_position_note,
        'work_experience_note' => $previousApp->work_experience_note,
        'willing_relocate'     => $previousApp->willing_relocate ? '1' : '0',
    ] : null;
    @endphp
    <script>
    function fillFromPrevious() {
      const d = @json($prevData);
      if (!d) return;

      const set = (name, val) => {
        const el = document.querySelector(`[name="${name}"]`);
        if (el && val !== null && val !== undefined) el.value = val;
      };
      const setTA = (name, val) => {
        const el = document.querySelector(`textarea[name="${name}"]`);
        if (el && val) el.value = val;
      };
      const setRadio = (name, val) => {
        const el = document.querySelector(`[name="${name}"][value="${val}"]`);
        if (el) el.checked = true;
      };
      const setSelect = (name, val) => {
        const el = document.querySelector(`select[name="${name}"]`);
        if (el && val) el.value = val;
      };

      set('full_name', d.full_name);
      setSelect('gender', d.gender);
      set('birth_place', d.birth_place);
      set('birth_date', d.birth_date);
      setTA('domicile_address', d.domicile_address);
      set('whatsapp_number', d.whatsapp_number);
      set('instagram_handle', d.instagram_handle);
      set('linkedin_url', d.linkedin_url);
      setSelect('education_level', d.education_level);
      set('major', d.major);
      set('school_name', d.school_name);
      set('graduation_year', d.graduation_year);
      set('str_valid_until', d.str_valid_until);
      set('other_position_note', d.other_position_note);
      setTA('work_experience_note', d.work_experience_note);
      setRadio('willing_relocate', d.willing_relocate);
    }
    </script>

    <form method="POST" action="{{ route('lamaran.store', $jobOpening) }}" enctype="multipart/form-data">
      @csrf

      {{-- Section 1: Data Diri --}}
      <section class="mt-4 bg-white border border-slate-200 rounded-[20px] py-[26px] px-7" style="border-top: 3px solid #042c6c;">
        <div class="flex gap-3 items-center mb-1">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#042c6c;background:rgba(4,44,108,0.08);">1</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800 tracking-[-0.3px]">Data Diri</h2>
        </div>
        <p class="m-0 mb-5 pl-10 text-[13px] text-slate-500">Isi sesuai dokumen identitas resmi (KTP).</p>
        <div class="sg-fields">
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Nama Lengkap <span class="text-red-700">*</span></label>
            <input name="full_name" value="{{ old('full_name', auth()->user()->name) }}" placeholder="Nama sesuai KTP" class="form-field {{ $errors->has('full_name') ? 'border-red-600' : '' }}" />
          </div>
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Jenis Kelamin <span class="text-red-700">*</span></label>
            <select name="gender" class="form-field {{ $errors->has('gender') ? 'border-red-600' : '' }}">
              <option value="">Pilih jenis kelamin</option>
              <option value="laki_laki" {{ old('gender') === 'laki_laki' ? 'selected' : '' }}>Laki-laki</option>
              <option value="perempuan" {{ old('gender') === 'perempuan' ? 'selected' : '' }}>Perempuan</option>
            </select>
          </div>
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Tempat Lahir</label>
            <input name="birth_place" value="{{ old('birth_place') }}" placeholder="Kota kelahiran (opsional)" class="form-field" />
          </div>
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Tanggal Lahir <span class="text-red-700">*</span></label>
            <input name="birth_date" type="date" value="{{ old('birth_date') }}" class="form-field {{ $errors->has('birth_date') ? 'border-red-600' : '' }}" />
          </div>
          <div class="col-span-full">
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Alamat Domisili <span class="text-red-700">*</span></label>
            <textarea name="domicile_address" rows="3" placeholder="Jalan, RT/RW, kelurahan, kecamatan, kota" class="form-field resize-y {{ $errors->has('domicile_address') ? 'border-red-600' : '' }}">{{ old('domicile_address') }}</textarea>
          </div>
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">No. WhatsApp <span class="text-red-700">*</span></label>
            <input name="whatsapp_number" value="{{ old('whatsapp_number') }}" placeholder="08xxxxxxxxxx" class="form-field {{ $errors->has('whatsapp_number') ? 'border-red-600' : '' }}" />
            <p class="mt-1.5 text-[12.5px] text-slate-500">Nomor aktif — dipakai untuk pemberitahuan jadwal.</p>
          </div>
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Instagram <span class="text-red-700">*</span></label>
            <input name="instagram_handle" value="{{ old('instagram_handle') }}" placeholder="@username" class="form-field {{ $errors->has('instagram_handle') ? 'border-red-600' : '' }}" />
          </div>
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Akun LinkedIn</label>
            <input name="linkedin_url" value="{{ old('linkedin_url') }}" placeholder="linkedin.com/in/... (opsional)" class="form-field" />
          </div>
        </div>
      </section>

      {{-- Section 2: Pendidikan --}}
      <section class="mt-4 bg-white border border-slate-200 rounded-[20px] py-[26px] px-7" style="border-top: 3px solid #0284c7;">
        <div class="flex gap-3 items-center mb-1">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#0284c7;background:rgba(2,132,199,0.1);">2</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800 tracking-[-0.3px]">Pendidikan</h2>
        </div>
        <p class="m-0 mb-5 pl-10 text-[13px] text-slate-500">Cantumkan pendidikan formal terakhir yang telah diselesaikan.</p>
        <div class="sg-fields">
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Jenjang Pendidikan <span class="text-red-700">*</span></label>
            <select name="education_level" class="form-field {{ $errors->has('education_level') ? 'border-red-600' : '' }}">
              <option value="">Pilih jenjang</option>
              <option value="sma_smk" {{ old('education_level') === 'sma_smk' ? 'selected' : '' }}>SMA/SMK</option>
              <option value="d3"       {{ old('education_level') === 'd3'       ? 'selected' : '' }}>D3</option>
              <option value="d4"       {{ old('education_level') === 'd4'       ? 'selected' : '' }}>D4</option>
              <option value="s1"       {{ old('education_level') === 's1'       ? 'selected' : '' }}>S1</option>
              <option value="s2"       {{ old('education_level') === 's2'       ? 'selected' : '' }}>S2</option>
              <option value="profesi"  {{ old('education_level') === 'profesi'  ? 'selected' : '' }}>Profesi</option>
            </select>
          </div>
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Jurusan <span class="text-red-700">*</span></label>
            <input name="major" value="{{ old('major') }}" placeholder="mis. Keperawatan" class="form-field {{ $errors->has('major') ? 'border-red-600' : '' }}" />
          </div>
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Nama Universitas / Instansi <span class="text-red-700">*</span></label>
            <input name="school_name" value="{{ old('school_name') }}" placeholder="Nama kampus atau sekolah" class="form-field {{ $errors->has('school_name') ? 'border-red-600' : '' }}" />
          </div>
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Tahun Lulus <span class="text-red-700">*</span></label>
            <input name="graduation_year" type="number" min="1970" max="{{ date('Y') }}" value="{{ old('graduation_year') }}" placeholder="{{ date('Y') - 2 }}" class="form-field {{ $errors->has('graduation_year') ? 'border-red-600' : '' }}" />
          </div>
          <div class="col-span-full">
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Tanggal Berlaku STR</label>
            <input name="str_valid_until" type="date" value="{{ old('str_valid_until') }}" class="form-field" />
            <p class="mt-1.5 text-[12.5px] text-slate-500">Khusus pelamar tenaga kesehatan. Kosongkan bila tidak berlaku.</p>
          </div>
        </div>
      </section>

      {{-- Section 3: Posisi & Pengalaman --}}
      <section class="mt-4 bg-white border border-slate-200 rounded-[20px] py-[26px] px-7" style="border-top: 3px solid #b45309;">
        <div class="flex gap-3 items-center mb-1">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#b45309;background:rgba(180,83,9,0.1);">3</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800 tracking-[-0.3px]">Posisi &amp; Pengalaman</h2>
        </div>
        <p class="m-0 mb-5 pl-10 text-[13px] text-slate-500">Melamar untuk <strong>{{ $jobOpening->title }}</strong> · {{ $jobOpening->company_name }}.</p>
        <div class="sg-fields">
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Posisi Lain yang Diminati</label>
            <input name="other_position_note" value="{{ old('other_position_note') }}" placeholder="Opsional — bila bersedia dipertimbangkan untuk posisi lain" class="form-field" />
          </div>
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Bersedia Ditempatkan di Luar Kota <span class="text-red-700">*</span></label>
            <div class="flex gap-[10px] flex-wrap">
              <label class="flex-1 basis-[140px] flex items-center gap-[10px] border border-slate-200 rounded-xl py-3 px-3.5 text-sm font-semibold text-slate-700 cursor-pointer">
                <input type="radio" name="willing_relocate" value="1" {{ old('willing_relocate') === '1' ? 'checked' : '' }} class="w-4 h-4 accent-navy cursor-pointer" /> Ya, bersedia
              </label>
              <label class="flex-1 basis-[140px] flex items-center gap-[10px] border border-slate-200 rounded-xl py-3 px-3.5 text-sm font-semibold text-slate-700 cursor-pointer">
                <input type="radio" name="willing_relocate" value="0" {{ old('willing_relocate') === '0' ? 'checked' : '' }} class="w-4 h-4 accent-navy cursor-pointer" /> Tidak bersedia
              </label>
            </div>
          </div>
          <div class="col-span-full">
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Pengalaman Kerja</label>
            <textarea name="work_experience_note" rows="4" placeholder="Tuliskan posisi, instansi, dan periode kerja Anda. Contoh: Perawat Pelaksana IGD — RS Harapan Sehat, Mar 2023 s.d. Jun 2026." class="form-field resize-y">{{ old('work_experience_note') }}</textarea>
          </div>
        </div>
      </section>

      {{-- Section 4: Ekspektasi & Ketersediaan --}}
      <section class="mt-4 bg-white border border-slate-200 rounded-[20px] py-[26px] px-7" style="border-top: 3px solid #047C54;">
        <div class="flex gap-3 items-center mb-1">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#047C54;background:rgba(4,124,84,0.1);">4</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800 tracking-[-0.3px]">Ekspektasi &amp; Ketersediaan</h2>
        </div>
        <p class="m-0 mb-5 pl-10 text-[13px] text-slate-500">Informasi ini membantu HR menyiapkan penawaran yang sesuai.</p>
        <div class="sg-fields">
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Gaji Terakhir <span class="text-red-700">*</span></label>
            <div class="flex items-center gap-[10px] border {{ $errors->has('last_salary') ? 'border-red-600' : 'border-slate-200' }} rounded-xl px-3.5 bg-white focus-within:border-navy transition-colors duration-150">
              <span class="text-[13.5px] font-bold text-slate-500">Rp</span>
              <input name="last_salary" type="number" min="0" value="{{ old('last_salary', 0) }}" class="flex-1 border-0 outline-none py-3 text-sm text-slate-800 bg-transparent" />
            </div>
            <p class="mt-1.5 text-[12.5px] text-slate-500">Tulis 0 bila belum pernah bekerja.</p>
          </div>
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Gaji yang Diharapkan <span class="text-red-700">*</span></label>
            <div class="flex items-center gap-[10px] border {{ $errors->has('expected_salary') ? 'border-red-600' : 'border-slate-200' }} rounded-xl px-3.5 bg-white focus-within:border-navy transition-colors duration-150">
              <span class="text-[13.5px] font-bold text-slate-500">Rp</span>
              <input name="expected_salary" type="number" min="0" value="{{ old('expected_salary') }}" class="flex-1 border-0 outline-none py-3 text-sm text-slate-800 bg-transparent" />
            </div>
          </div>
          <div class="col-span-full">
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">Bersedia Bergabung <span class="text-red-700">*</span></label>
            <select name="availability" class="form-field {{ $errors->has('availability') ? 'border-red-600' : '' }}">
              <option value="">Pilih ketersediaan</option>
              <option value="immediately"           {{ old('availability') === 'immediately'           ? 'selected' : '' }}>Segera (dapat langsung bergabung)</option>
              <option value="2_weeks"               {{ old('availability') === '2_weeks'               ? 'selected' : '' }}>Dalam 2 minggu</option>
              <option value="1_month"               {{ old('availability') === '1_month'               ? 'selected' : '' }}>Dalam 1 bulan</option>
              <option value="more_than_2_months"    {{ old('availability') === 'more_than_2_months'    ? 'selected' : '' }}>Lebih dari 2 bulan</option>
            </select>
          </div>
        </div>
      </section>

      {{-- Section 5: Dokumen --}}
      <section class="mt-4 bg-white border border-slate-200 rounded-[20px] py-[26px] px-7" style="border-top: 3px solid #B9308C;">
        <div class="flex gap-3 items-center mb-1">
          <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12.5px] font-extrabold" style="color:#B9308C;background:rgba(185,48,140,0.1);">5</div>
          <h2 class="m-0 text-[17px] font-extrabold text-slate-800 tracking-[-0.3px]">Dokumen</h2>
        </div>
        <p class="m-0 mb-5 pl-10 text-[13px] text-slate-500">CV wajib berformat PDF, maksimal 2 MB per berkas.</p>
        <div class="sg-fields">
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">File CV <span class="text-red-700">*</span></label>
            <label class="block border-2 border-dashed {{ $errors->has('cv_file') ? 'border-red-600' : 'border-slate-300' }} rounded-2xl py-6 px-5 text-center bg-slate-50 cursor-pointer hover:border-navy hover:bg-navy/[0.02] transition-colors duration-150">
              <i class="fa-solid fa-file-pdf text-[22px] text-slate-400"></i>
              <div class="mt-2.5 text-sm font-bold text-slate-800" id="cv-label">Unggah CV (PDF)</div>
              <div class="mt-1 text-[12.5px] text-slate-500">Klik untuk memilih file · Maks. 2 MB</div>
              <input type="file" name="cv_file" accept=".pdf" class="hidden" onchange="document.getElementById('cv-label').textContent = this.files[0]?.name || 'Unggah CV (PDF)'" />
            </label>
            @error('cv_file')<p class="mt-1.5 text-[12.5px] text-red-600">{{ $message }}</p>@enderror
          </div>
          <div>
            <label class="block mb-[7px] text-[13px] font-bold text-slate-700">File Sertifikat Keahlian</label>
            <label class="block border-2 border-dashed border-slate-300 rounded-2xl py-6 px-5 text-center bg-slate-50 cursor-pointer hover:border-navy hover:bg-navy/[0.02] transition-colors duration-150">
              <i class="fa-solid fa-certificate text-[22px] text-slate-400"></i>
              <div class="mt-2.5 text-sm font-bold text-slate-800" id="cert-label">Unggah Sertifikat</div>
              <div class="mt-1 text-[12.5px] text-slate-500">Opsional · PDF atau JPG</div>
              <input type="file" name="certificate_file" accept=".pdf,.jpg,.jpeg,.png" class="hidden" onchange="document.getElementById('cert-label').textContent = this.files[0]?.name || 'Unggah Sertifikat'" />
            </label>
          </div>
        </div>

        {{-- Agreement checkbox --}}
        <label class="mt-[22px] flex gap-[11px] items-start p-4 rounded-[14px] bg-slate-50 border border-slate-200 cursor-pointer">
          <input type="checkbox" name="agreement" required class="w-[17px] h-[17px] mt-px accent-navy cursor-pointer shrink-0" />
          <span class="text-[13px] leading-[1.6] text-slate-600">Saya menyatakan seluruh data dan dokumen yang saya kirimkan adalah benar, dan bersedia menerima konsekuensi apabila di kemudian hari ditemukan ketidaksesuaian.</span>
        </label>

        {{-- Submit --}}
        <div class="mt-[22px] pt-5 border-t border-slate-100 flex flex-wrap gap-3 justify-between items-center">
          <span class="text-[12.5px] text-slate-500">Proses rekrutmen Syifa Global Group tidak dipungut biaya.</span>
          <div class="flex gap-[10px]">
            <a href="{{ route('welcome') }}" class="px-5 py-3 rounded-xl border border-slate-300 bg-white text-slate-700 text-[13.5px] font-bold no-underline hover:border-slate-400 transition-colors duration-150">Batal</a>
            <button type="submit" class="px-[26px] py-3 rounded-xl border-0 bg-sgg-green hover:bg-sgg-green-hover text-white text-[13.5px] font-bold cursor-pointer transition-colors duration-150">
              <i class="fa-solid fa-paper-plane mr-2 text-xs"></i>Kirim Lamaran
            </button>
          </div>
        </div>
      </section>

    </form>
  </main>
</div>

</x-public-layout>
