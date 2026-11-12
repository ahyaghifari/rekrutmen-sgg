{{-- Satu isian biodata dari katalog (config/biodata.php) sesuai master perusahaan. $f = item BiodataFields, $candidate. --}}
@php
  $def = $f['def'];
  $key = $f['key'];
  $mark = $f['required'] ? ' *' : '';
  $wide = $f['block'] || in_array($f['type'], ['pair', 'str', 'address'], true) ? 'col-span-full' : '';
  $lab = 'block mb-[7px] text-[13px] font-bold text-slate-700';
@endphp

@if(in_array($key, ['name', 'nik'], true))
{{-- Nama (sesuai KTP) dan NIK diambil dari akun saat mendaftar: terkunci dan tidak ikut dikirim. --}}
<div class="{{ $wide }}"><label class="{{ $lab }}">{{ $f['label'] }}{{ $mark }}</label>
  <input type="text" value="{{ $key === 'name' ? ($candidate->user?->name ?: $candidate->name) : ($candidate->user?->nik ?: $candidate->nik) }}" disabled class="form-field cursor-not-allowed opacity-70" />
  <div class="mt-1 text-[11.5px] text-slate-400">Diambil dari akun Anda.</div></div>

@elseif(array_key_exists($key, $candidate->lockedFields()))
{{-- Jenis kelamin, tempat & tanggal lahir diambil dari form lamaran: terkunci dan tidak ikut dikirim. --}}
<div class="{{ $wide }}"><label class="{{ $lab }}">{{ $f['label'] }}{{ $mark }}</label>
  <input type="text" value="{{ $key === 'birth_date' ? \Illuminate\Support\Carbon::parse($candidate->lockedFields()[$key])->translatedFormat('d F Y') : $candidate->lockedFields()[$key] }}" disabled class="form-field cursor-not-allowed opacity-70" />
  <div class="mt-1 text-[11.5px] text-slate-400">Diambil dari form lamaran.</div></div>

@elseif($f['type'] === 'text')
<div class="{{ $wide }}"><label class="{{ $lab }}">{{ $f['label'] }}{{ $mark }}</label>
  <input type="text" name="{{ $key }}" value="{{ old($key, $candidate->{$key} ?? ($def['default'] ?? '')) }}"
    @if(isset($def['maxlength'])) maxlength="{{ $def['maxlength'] }}" @endif
    @if(isset($def['placeholder'])) placeholder="{{ $def['placeholder'] }}" @endif
    class="form-field" /></div>

@elseif($f['type'] === 'date')
<div class="{{ $wide }}"><label class="{{ $lab }}">{{ $f['label'] }}{{ $mark }}</label>
  <input type="date" name="{{ $key }}" value="{{ old($key, $candidate->{$key}?->format('Y-m-d')) }}" class="form-field" /></div>

@elseif($f['type'] === 'select')
<div class="{{ $wide }}"><label class="{{ $lab }}">{{ $f['label'] }}{{ $mark }}</label>
  <select name="{{ $key }}" class="form-field">
    <option value="">Pilih...</option>
    @foreach($def['options'] as $opt)
    <option value="{{ $opt }}" @selected(old($key, $candidate->{$key}) === $opt)>{{ $opt }}</option>
    @endforeach
  </select></div>

@elseif($f['type'] === 'pair')
<div class="col-span-full"><label class="{{ $lab }}">{{ $f['label'] }}{{ $mark }} <span class="font-normal text-slate-400">(nama &amp; nomor)</span></label>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <input type="text" name="{{ $def['columns'][0] }}" value="{{ old($def['columns'][0], $candidate->{$def['columns'][0]}) }}" placeholder="Nama" class="form-field" />
    <input type="text" name="{{ $def['columns'][1] }}" value="{{ old($def['columns'][1], $candidate->{$def['columns'][1]}) }}" placeholder="Nomor telepon" class="form-field" />
  </div></div>

@elseif($f['type'] === 'str')
{{-- STR hanya ditanyakan di rumah sakit (companies.is_hospital): RSU Syifa Medika Banjarbaru dan Barabai. --}}
@if($candidate->isHospital())
<div class="col-span-full"><label class="{{ $lab }}">No. STR (Surat Tanda Registrasi) *</label>
  <input type="text" name="no_str" value="{{ old('no_str', $candidate->no_str) }}" class="form-field" /></div>
<div class="col-span-full" x-data="{ lifetime: {{ old('str_seumur_hidup', $candidate->str_seumur_hidup) ? 'true' : 'false' }} }">
  <label class="flex items-center gap-2 mb-3 text-[13px] font-bold text-slate-700 cursor-pointer">
    <input type="checkbox" name="str_seumur_hidup" value="1" x-model="lifetime" class="rounded border-slate-300">
    STR berlaku seumur hidup
  </label>
  <div x-show="!lifetime" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div><label class="{{ $lab }}">STR Berlaku Mulai *</label>
      <input type="date" name="str_berlaku_mulai" :disabled="lifetime" value="{{ old('str_berlaku_mulai', $candidate->str_berlaku_mulai?->format('Y-m-d')) }}" class="form-field" /></div>
    <div><label class="{{ $lab }}">STR Berlaku Sampai *</label>
      <input type="date" name="str_berlaku_sampai" :disabled="lifetime" value="{{ old('str_berlaku_sampai', $candidate->str_berlaku_sampai?->format('Y-m-d')) }}" class="form-field" /></div>
  </div>
</div>
@include('candidate-profile.partials.str-file')
@endif

@elseif($f['type'] === 'address')
<div class="col-span-full"><label class="{{ $lab }}">Alamat{{ $mark }}</label>
  <textarea name="ktp_address" rows="2" class="form-field resize-y">{{ old('ktp_address', $candidate->ktp_address) }}</textarea></div>
{{-- Pilihan wilayah dari wilayah.id (sama dengan form offering); tanpa `required` supaya tab lain tetap bisa disimpan. --}}
<x-wilayah-picker prefix="" :required="false" class="col-span-full"
  :values="['province' => $candidate->province ?? '', 'city' => $candidate->city ?? '', 'district' => $candidate->district ?? '', 'village' => $candidate->village ?? '']" />
@endif
