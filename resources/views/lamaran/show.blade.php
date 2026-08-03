<x-app-layout>
<x-slot name="title">Detail Lamaran — {{ $application->jobOpening->title }}</x-slot>

@php
$statusMap = [
    'applied'   => ['label' => 'Lamaran Masuk',    'badge' => 'badge-applied',   'icon' => 'fa-paper-plane'],
    'screening' => ['label' => 'Screening',          'badge' => 'badge-screening', 'icon' => 'fa-magnifying-glass'],
    'interview' => ['label' => 'Wawancara',          'badge' => 'badge-interview', 'icon' => 'fa-comments'],
    'passed'    => ['label' => 'Diterima',           'badge' => 'badge-passed',    'icon' => 'fa-circle-check'],
    'rejected'  => ['label' => 'Tidak Dilanjutkan', 'badge' => 'badge-rejected',  'icon' => 'fa-circle-xmark'],
];
$educationMap = ['sma_smk' => 'SMA/SMK', 'd3' => 'D3', 'd4' => 'D4', 's1' => 'S1', 's2' => 'S2', 'profesi' => 'Profesi'];
$availabilityMap = [
    'immediately'        => 'Segera (dapat langsung bergabung)',
    '2_weeks'            => 'Dalam 2 minggu',
    '1_month'            => 'Dalam 1 bulan',
    'more_than_2_months' => 'Lebih dari 2 bulan',
];
$steps = [
    ['label' => 'Lamaran Masuk', 'sub' => 'Formulir terkirim',   'icon' => 'fa-paper-plane'],
    ['label' => 'Screening',      'sub' => 'Tinjauan berkas',     'icon' => 'fa-magnifying-glass'],
    ['label' => 'Wawancara',      'sub' => 'Jadwal akan dikirim', 'icon' => 'fa-comments'],
    ['label' => 'Keputusan',      'sub' => $application->status === 'passed' ? 'Selamat, diterima!' : ($application->status === 'rejected' ? 'Tidak dilanjutkan' : 'Menunggu keputusan'), 'icon' => $application->status === 'passed' ? 'fa-circle-check' : ($application->status === 'rejected' ? 'fa-circle-xmark' : 'fa-award')],
];
$currentStep = match($application->status) {
    'applied' => 0, 'screening' => 1, 'interview' => 2, 'passed', 'rejected' => 3, default => 0
};
$isRejected = $application->status === 'rejected';
@endphp

{{-- Back --}}
<div class="mb-5 flex items-center gap-3">
  <a href="{{ route('dashboard') }}"
     class="w-[36px] h-[36px] shrink-0 rounded-[11px] border border-slate-200 bg-white text-slate-600 grid place-items-center no-underline hover:border-slate-300 transition-colors duration-150">
    <i class="fa-solid fa-arrow-left text-sm"></i>
  </a>
  <div>
    <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.06em]">Detail Lamaran</div>
    <div class="text-[15px] font-extrabold text-slate-800 tracking-[-0.3px]">{{ $application->jobOpening->title }}</div>
  </div>
</div>

{{-- Position card --}}
<div class="relative overflow-hidden rounded-[20px] text-white px-7 py-6"
     style="background:linear-gradient(135deg,#042c6c 0%,#0b47a1 100%);">
  <div class="absolute w-[220px] h-[220px] rounded-full -right-[60px] -top-[100px] pointer-events-none bg-white/[0.06]"></div>
  <div class="absolute w-[110px] h-[110px] rounded-[26px] rotate-[22deg] right-[150px] -bottom-[55px] pointer-events-none bg-magenta/[0.18]"></div>
  <div class="relative z-[1] flex flex-wrap items-start justify-between gap-4">
    <div>
      <div class="text-[11.5px] font-bold tracking-[0.08em] uppercase text-white/70">Melamar untuk</div>
      <h1 class="mt-2 mb-1 text-2xl font-extrabold tracking-[-0.5px]">{{ $application->jobOpening->title }}</h1>
      <div class="text-[13.5px] text-white/80">{{ $application->jobOpening->company_name }}</div>
      <div class="mt-2 text-[12.5px] text-white/60">Dikirim {{ $application->created_at->translatedFormat('j F Y, H:i') }} WIB</div>
    </div>
    <span class="badge {{ $statusMap[$application->status]['badge'] ?? 'badge-applied' }} mt-1 text-[12px] px-[12px] py-[5px]">
      <i class="fa-solid {{ $statusMap[$application->status]['icon'] ?? 'fa-paper-plane' }}"></i>
      {{ $statusMap[$application->status]['label'] ?? $application->status }}
    </span>
  </div>
</div>

{{-- Status timeline --}}
<div class="mt-4 bg-white border border-slate-200 rounded-[18px] px-6 py-5">
  <div class="text-[12px] font-bold uppercase tracking-[0.06em] text-slate-500 mb-5">Tahap Seleksi</div>
  <div style="display:flex;gap:0;">
    @foreach($steps as $i => $step)
    @php
      $done    = $i < $currentStep;
      $current = $i === $currentStep;
      $future  = $i > $currentStep;
      $circleBg = $done ? '#042c6c' : ($current ? ($isRejected && $i === 3 ? '#dc2626' : '#042c6c') : '#f1f5f9');
      $circleColor = ($done || $current) ? '#fff' : '#94a3b8';
      $circleBorder = $future ? '2px solid #e2e8f0' : 'none';
      $labelColor = $future ? '#94a3b8' : '#042c6c';
      $lineLeft   = $done || $current ? '#042c6c' : '#e2e8f0';
      $lineRight  = $done ? '#042c6c' : '#e2e8f0';
    @endphp
    <div style="flex:1;text-align:center;position:relative;padding:0 4px;">
      @if($i > 0)
      <div style="position:absolute;top:17px;left:0;right:50%;height:2px;background:{{ $lineLeft }};"></div>
      @endif
      @if($i < count($steps) - 1)
      <div style="position:absolute;top:17px;left:50%;right:0;height:2px;background:{{ $lineRight }};"></div>
      @endif
      <div style="width:34px;height:34px;border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center;font-size:12px;position:relative;z-index:1;background:{{ $circleBg }};color:{{ $circleColor }};border:{{ $circleBorder }};">
        @if($done)
          <i class="fa-solid fa-check"></i>
        @else
          <i class="fa-solid {{ $step['icon'] }}"></i>
        @endif
      </div>
      <div style="margin-top:8px;font-size:11.5px;font-weight:700;line-height:1.3;color:{{ $labelColor }};">{{ $step['label'] }}</div>
      <div style="margin-top:3px;font-size:10.5px;color:#94a3b8;line-height:1.3;">{{ $step['sub'] }}</div>
    </div>
    @endforeach
  </div>
</div>

{{-- Data sections --}}

{{-- Section 1: Data Diri --}}
<div class="mt-4 bg-white border border-slate-200 rounded-[18px] py-[22px] px-6">
  <div class="flex gap-3 items-center mb-4">
    <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12px] font-extrabold text-navy bg-navy/[0.08]">1</div>
    <h2 class="m-0 text-[16px] font-extrabold text-slate-800 tracking-[-0.3px]">Data Diri</h2>
  </div>
  <div class="sg-fields">
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Nama Lengkap</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->full_name }}</div>
    </div>
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Jenis Kelamin</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->gender === 'laki_laki' ? 'Laki-laki' : 'Perempuan' }}</div>
    </div>
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">No. WhatsApp</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->whatsapp_number }}</div>
    </div>
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Usia</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->age }} tahun</div>
    </div>
    @if($application->birth_place)
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Tempat Lahir</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->birth_place }}</div>
    </div>
    @endif
    @if($application->birth_date)
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Tanggal Lahir</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->birth_date->translatedFormat('j F Y') }}</div>
    </div>
    @endif
    <div class="{{ isset($application->birth_place) || isset($application->birth_date) ? '' : 'col-span-full' }}">
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Alamat Domisili</div>
      <div class="text-[14px] font-semibold text-slate-800 whitespace-pre-line">{{ $application->domicile_address }}</div>
    </div>
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Instagram</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->instagram_handle }}</div>
    </div>
    @if($application->linkedin_url)
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">LinkedIn</div>
      <div class="text-[14px] font-semibold text-slate-800 truncate">{{ $application->linkedin_url }}</div>
    </div>
    @endif
  </div>
</div>

{{-- Section 2: Pendidikan --}}
<div class="mt-3 bg-white border border-slate-200 rounded-[18px] py-[22px] px-6">
  <div class="flex gap-3 items-center mb-4">
    <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12px] font-extrabold text-navy bg-navy/[0.08]">2</div>
    <h2 class="m-0 text-[16px] font-extrabold text-slate-800 tracking-[-0.3px]">Pendidikan</h2>
  </div>
  <div class="sg-fields">
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Jenjang</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $educationMap[$application->education_level] ?? $application->education_level }}</div>
    </div>
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Jurusan</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->major }}</div>
    </div>
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Institusi</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->school_name }}</div>
    </div>
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Tahun Lulus</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->graduation_year }}</div>
    </div>
    @if($application->str_valid_until)
    <div class="col-span-full">
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Berlaku STR s.d.</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->str_valid_until->translatedFormat('j F Y') }}</div>
    </div>
    @endif
  </div>
</div>

{{-- Section 3: Posisi & Pengalaman --}}
<div class="mt-3 bg-white border border-slate-200 rounded-[18px] py-[22px] px-6">
  <div class="flex gap-3 items-center mb-4">
    <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12px] font-extrabold text-navy bg-navy/[0.08]">3</div>
    <h2 class="m-0 text-[16px] font-extrabold text-slate-800 tracking-[-0.3px]">Posisi &amp; Pengalaman</h2>
  </div>
  <div class="sg-fields">
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Posisi Dilamar</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->jobOpening->title }}</div>
    </div>
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Bersedia Relokasi</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->willing_relocate ? 'Ya, bersedia' : 'Tidak bersedia' }}</div>
    </div>
    @if($application->other_position_note)
    <div class="col-span-full">
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Posisi Lain yang Diminati</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $application->other_position_note }}</div>
    </div>
    @endif
    @if($application->work_experience_note)
    <div class="col-span-full">
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Pengalaman Kerja</div>
      <div class="text-[14px] font-semibold text-slate-800 whitespace-pre-line">{{ $application->work_experience_note }}</div>
    </div>
    @endif
  </div>
</div>

{{-- Section 4: Ekspektasi --}}
<div class="mt-3 bg-white border border-slate-200 rounded-[18px] py-[22px] px-6">
  <div class="flex gap-3 items-center mb-4">
    <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12px] font-extrabold text-navy bg-navy/[0.08]">4</div>
    <h2 class="m-0 text-[16px] font-extrabold text-slate-800 tracking-[-0.3px]">Ekspektasi &amp; Ketersediaan</h2>
  </div>
  <div class="sg-fields">
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Gaji Terakhir</div>
      <div class="text-[14px] font-semibold text-slate-800">Rp {{ number_format($application->last_salary, 0, ',', '.') }}</div>
    </div>
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Gaji Diharapkan</div>
      <div class="text-[14px] font-semibold text-slate-800">Rp {{ number_format($application->expected_salary, 0, ',', '.') }}</div>
    </div>
    <div class="col-span-full">
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-1">Ketersediaan Bergabung</div>
      <div class="text-[14px] font-semibold text-slate-800">{{ $availabilityMap[$application->availability] ?? $application->availability }}</div>
    </div>
  </div>
</div>

{{-- Section 5: Dokumen --}}
<div class="mt-3 bg-white border border-slate-200 rounded-[18px] py-[22px] px-6">
  <div class="flex gap-3 items-center mb-4">
    <div class="w-7 h-7 shrink-0 rounded-[9px] grid place-items-center text-[12px] font-extrabold text-sgg-green bg-sgg-green/[0.1]">5</div>
    <h2 class="m-0 text-[16px] font-extrabold text-slate-800 tracking-[-0.3px]">Dokumen</h2>
  </div>
  <div class="sg-fields">
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-2">CV</div>
      @if($application->cv_file_path)
      <a href="{{ asset('storage/' . $application->cv_file_path) }}" target="_blank"
         class="inline-flex items-center gap-2 px-4 py-[9px] rounded-[10px] border border-slate-200 bg-slate-50 text-[13px] font-semibold text-slate-700 no-underline hover:border-navy hover:text-navy transition-colors duration-150">
        <i class="fa-solid fa-file-pdf text-red-500"></i>Lihat CV
      </a>
      @else
      <span class="text-[13px] text-slate-400">—</span>
      @endif
    </div>
    <div>
      <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-[0.05em] mb-2">Sertifikat</div>
      @if($application->certificate_file_path)
      <a href="{{ asset('storage/' . $application->certificate_file_path) }}" target="_blank"
         class="inline-flex items-center gap-2 px-4 py-[9px] rounded-[10px] border border-slate-200 bg-slate-50 text-[13px] font-semibold text-slate-700 no-underline hover:border-navy hover:text-navy transition-colors duration-150">
        <i class="fa-solid fa-certificate text-amber-500"></i>Lihat Sertifikat
      </a>
      @else
      <span class="text-[13px] text-slate-400">Tidak diunggah</span>
      @endif
    </div>
  </div>
</div>

</x-app-layout>
