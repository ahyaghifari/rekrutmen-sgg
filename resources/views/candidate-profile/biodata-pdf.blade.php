<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Form Biodata Pelamar — {{ $d['name'] }}</title>
@php
  $fd = str_replace('\\', '/', storage_path('fonts'));
  $faces = ['Verdana' => ['normal' => 'verdana', 'bold' => 'verdanab', 'italic' => 'verdanai'], 'Calibri' => ['normal' => 'calibri', 'bold' => 'calibrib', 'italic' => 'calibrii'], 'Arial' => ['normal' => 'arial', 'bold' => 'arialbd', 'italic' => 'ariali'], 'ArialBlack' => ['normal' => 'ariblk']];
@endphp
<style>
@foreach($faces as $family => $files)
@foreach($files as $style => $file)
@if(file_exists("$fd/$file.ttf"))
  @font-face { font-family: '{{ $family }}'; font-style: {{ $style === 'italic' ? 'italic' : 'normal' }}; font-weight: {{ $style === 'bold' ? 'bold' : 'normal' }}; src: url('{{ $fd }}/{{ $file }}.ttf') format('truetype'); }
@endif
@endforeach
@endforeach
  /* Tata letak meniru Form Biodata Pelamar.docx (knowledge/form-biodata-1..4.png); latar = kop surat perusahaan,
     sama dengan surat penawaran. */
  /* Margin bawah: kop RS hanya di atas (1,6 cm); latar perusahaan lain punya footer alamat (2,7 cm). */
  /* Margin atas holding lebih longgar (3,7 cm); latar .bg digeser sama besar supaya tetap menutup halaman. */
  @php $topPt = $d['hospital'] ? 96 : 105; @endphp
  @page { margin: {{ $d['hospital'] ? '3.4cm' : '3.7cm' }} 1.2cm {{ $d['hospital'] ? '1.6cm' : '2.4cm' }} 2.3cm; }
  body { font-family: Verdana, 'DejaVu Sans', sans-serif; font-size: 10pt; color: #111; margin: 0; }
  .bg { position: fixed; top: -{{ $topPt }}pt; left: -65pt; width: 595pt; height: 842pt; }
  /* Isi harus position: relative supaya tergambar di ATAS latar (elemen fixed dompdf menimpa yang statis). */
  .content { position: relative; }
  table { width: 100%; border-collapse: collapse; }
  /* Judul: Arial (dompdf memetakannya ke Helvetica) 10 pt, tebal, garis bawah. */
  h1 { text-align: center; font-family: Arial, Helvetica, sans-serif; font-weight: bold; font-size: 11pt; text-decoration: underline; margin: 0; }
  .h { font-family: Calibri, 'DejaVu Sans', sans-serif; font-weight: bold; font-size: 11pt; margin: 14px 0 4px; text-transform: uppercase; page-break-after: avoid; }
  /* Perusahaan non-RS (holding): judul dan judul bagian Verdana Bold, sisanya Arial. */
  body.nh { font-family: Arial, Helvetica, sans-serif; }
  .nh h1 { font-family: Verdana, 'DejaVu Sans', sans-serif; font-weight: bold; font-size: 14pt; }
  .nh .h { font-family: Verdana, 'DejaVu Sans', sans-serif; }
  .pb { page-break-before: always; margin-top: 0; }
  .h.first { margin-top: -2px; }
  .h i { font-weight: normal; text-transform: none; }

  .title td { border: none; padding: 0; vertical-align: top; }
  /* Pas foto 3 x 4 cm: ukuran luar termasuk garis (satuan pt, bukan cm: dompdf memetakan cm sedikit lebih kecil). */
  .photo { width: 84.3pt; height: 112.6pt; border: 1px solid #000; text-align: center; }

  .box { border: 1px solid #000; padding: 7px 9px 9px; }
  table.f td { border: none; padding: 1.6px 0; vertical-align: bottom; }
  td.l { width: 33%; white-space: nowrap; }
  td.c { width: 2%; }
  table.f td.v, table.f td.v2, table.j td.v { border-bottom: 1px dotted #000; }
  table.f td.v { padding-right: 10px; }
  /* Label kolom kedua rata kanan dan selebar teksnya, supaya menempel ke nilainya (lebar kolom mengikuti "Masa Berlaku"). */
  table.f td.l2 { width: 1%; padding-left: 8px; padding-right: 8px; white-space: nowrap; text-align: right; }
  table.f td.v2 { width: 26%; padding-left: 8px; }
  .sel { font-weight: bold; }
  .no { text-decoration: line-through; }

  tr { page-break-inside: avoid; }
  table.g td, table.g th { border: 1px solid #000; padding: 0.8px 4px; height: 11px; }
  table.g th { background: #d9d9d9; text-align: center; }

  .job { font-weight: bold; margin: 5px 0 2px; page-break-after: avoid; }
  table.j { margin-left: 0.7cm; width: 96%; page-break-inside: avoid; }
  table.j td { border: none; padding: 1.4px 0; vertical-align: bottom; }
  table.j td.l { width: 4.4cm; white-space: nowrap; }
  table.j td.c { width: 0.4cm; }

  table.q td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; }
  .dl { border-bottom: 1px dotted #000; min-height: 12px; padding-top: 2px; }
  .ol { margin: 3px 0 2px 0.7cm; }
  .ol div { margin: 3px 0; }
  .num { font-weight: bold; }
  p.stmt { text-align: justify; margin: 9px 0 0; line-height: 1.5; page-break-inside: avoid; }
  .sign { page-break-inside: avoid; margin-top: 8px; }
</style>
</head>
<body @class(['nh' => ! $d['hospital']])>
<img class="bg" src="{{ $d['background'] }}">
<div class="content">
@php
  $show = fn ($v) => filled($v) ? $v : '';
  $dots = fn ($v, $dot = '………………') => filled($v) ? $v : $dot;
  // Kotak persegi digambar dengan CSS (bukan glyph font yang tidak persegi), diberi jarak ke teks.
  $box = fn ($on) => new \Illuminate\Support\HtmlString('<span style="display:inline-block;width:9px;height:9px;border:1px solid #000;margin-right:6px;text-align:center;font-size:11px;line-height:8.5px;font-family:DejaVu Sans;">'.($on ? '&#x2715;' : '&nbsp;').'</span>');
  $raw = $d['raw'];
  $short = ['blood_type', 'phone', 'religion']; // pasangan kolom kedua pada baris yang sama dengan isian di atasnya
  $pairs = ['nickname' => 'blood_type', 'email' => 'phone', 'nationality' => 'religion'];
  $rows = $d['personal'];
  $pad = fn (array $list, int $min, int $cols) => array_pad($list, $min, array_fill(0, $cols, ''));
  // kelas penanda pilihan: dipilih = sel, bukan yang dipilih = no (dicoret) kalau sudah ada pilihan, kosong = form belum diisi
  $mark = fn ($selected, $any) => $selected ? 'sel' : ($any ? 'no' : '');
@endphp

<table class="title">
  <tr>
    {{-- Holding: sel kosong selebar kolom foto di kiri, supaya judul tepat di tengah halaman. --}}
    @unless($d['hospital'])<td style="width:3.1cm;"></td>@endunless
    <td style="padding-top:26px;">@if($d['hospital'])<h1>FORM BIODATA PELAMAR</h1>@else<h1>FORMULIR ISIAN PELAMAR</h1>@endif</td>
    <td style="width:3.1cm;"><div class="photo"><div style="padding-top:6px;">Pas Foto<br>Terbaru</div></div></td>
  </tr>
</table>

<div class="h first">Data Pribadi</div>
<div class="box">
<table class="f">
  @for($i = 0; $i < count($rows); $i++)
    @php
      [$label, $value, $key, $placed] = $rows[$i];
      $next = $rows[$i + 1] ?? null;
      $paired = $next && ! $placed && ! $next[3] && ($pairs[$key] ?? null) === $next[2];
    @endphp
    @if($key === 'name')
      @php
        $g = $raw['gender'] ? mb_strtoupper(mb_substr($raw['gender'], 0, 1)) : null;
        if ($next && $next[2] === 'gender') { $i++; }
      @endphp
      <tr><td class="l">{{ $label }}</td><td class="c">:</td><td class="v" colspan="2">{{ $value }}</td>
        <td colspan="2" style="text-align:right;font-weight:bold;"><span class="{{ $mark($g === 'L', (bool) $g) }}">L</span> / <span class="{{ $mark($g === 'P', (bool) $g) }}">P</span></td></tr>
    @elseif($key === 'gender')
      {{-- sudah ditampilkan sebagai L/P pada baris nama --}}
    @elseif($paired)
      <tr><td class="l">{{ $label }}</td><td class="c">:</td><td class="v">{{ $value }}</td><td class="l2">{{ $next[0] }}</td><td class="v2" colspan="1">{{ $next[1] }}</td></tr>
      @php $i++; @endphp
    @elseif($key === 'status_marital')
      @php $st = $raw['status']; @endphp
      <tr><td class="l">{{ $label }}</td><td class="c">:</td><td colspan="4">
        <span class="{{ $mark($st === 'Belum Menikah', (bool) $st) }}">Belum Menikah</span> &nbsp;/&nbsp;
        <span class="{{ $mark($st === 'Sudah Menikah', (bool) $st) }}">Menikah</span> &nbsp;/&nbsp;
        <span class="{{ $mark($st === 'Janda', (bool) $st) }}">Janda</span> &nbsp;/&nbsp;
        <span class="{{ $mark($st === 'Duda', (bool) $st) }}">Duda</span></td></tr>
    @elseif($key === 'str')
      <tr><td class="l">No. STR</td><td class="c">:</td><td class="v" colspan="4">{{ $show($value) }}</td></tr>
      <tr><td class="l">{{ $label }}</td><td class="c">:</td><td class="v"><span class="{{ $mark($raw['str_active'], (bool) $value) }}">Aktif</span> / <span class="{{ $mark($value && ! $raw['str_active'], (bool) $value) }}">Tidak Aktif</span></td><td class="l2">Masa Berlaku</td><td class="v2">{{ $show($raw['str_until']) }}</td></tr>
    @elseif($key === 'str')
      <tr><td class="l">{{ $label }}</td><td class="c">:</td><td class="v">{{ $show($value) }} &nbsp; <span class="{{ $raw['str_active'] ? 'sel' : '' }}">Aktif</span> / <span class="{{ $value && ! $raw['str_active'] ? 'sel' : '' }}">Tidak Aktif</span></td><td class="l2">Masa Berlaku</td><td class="v2">{{ $show($raw['str_until']) }}</td></tr>
    @else
      <tr><td class="l">{{ $label }}</td><td class="c">:</td><td class="v" colspan="4">{{ $value }}</td></tr>
    @endif
  @endfor
</table>
</div>

<div class="h">Hubungan Keluarga (termasuk diri Anda sendiri)</div>
<table class="g">
  <thead><tr><th style="width:14%;">Hubungan</th><th style="width:6%;">L/P</th><th>Nama</th><th style="width:13%;">Tanggal Lahir</th><th style="width:15%;">Pendidikan</th><th style="width:15%;">Pekerjaan</th></tr></thead>
  <tbody>
  @foreach($d['family'] as $r)
  @php
    // Saudara/Anak bernomor: baris pertama "Saudara ... 1", baris berikutnya hanya angka rata kanan (seperti form).
    $isNum = preg_match('/^(Saudara|Anak) ([0-9])$/', $r[0], $m);
  @endphp
  <tr>@foreach($r as $n => $cell)
    @if($n === 0 && $isNum)
      <td style="text-align:right;">@if($m[2] === '1')<span style="float:left;">{{ $m[1] }}</span>@endif{{ $m[2] }}</td>
    @else
      <td @if($n === 1) style="text-align:center;" @endif>{{ $cell }}</td>
    @endif
  @endforeach</tr>
  @endforeach
  </tbody>
</table>

<div class="h">Pendidikan Formal</div>
<table class="g">
  <tr><th rowspan="2" style="width:17%;">Tingkat</th><th rowspan="2">Nama Sekolah</th><th rowspan="2" style="width:20%;">Jurusan</th><th rowspan="2" style="width:7%;">IPK</th><th colspan="2">Tahun</th></tr>
  <tr><th style="width:8%;">Dari</th><th style="width:8%;">Sampai</th></tr>
  @foreach($d['educations'] as $r)
  <tr>@foreach($r as $cell)<td>{{ $cell }}</td>@endforeach</tr>
  @endforeach
</table>

<div class="h">Pendidikan Informal / Pelatihan</div>
<table class="g">
  <tr><th style="width:13%;">Tipe</th><th>Nama Institusi</th><th style="width:15%;">Materi</th><th style="width:14%;">Waktu</th><th style="width:24%;">Tempat</th></tr>
  @foreach($pad($d['trainings'], 5, 5) as $r)
  <tr>@foreach($r as $cell)<td>{{ $cell }}</td>@endforeach</tr>
  @endforeach
</table>

<div class="h">Pengalaman Organisasi</div>
<table class="g">
  <tr><th>Organisasi</th><th style="width:14%;">Bidang</th><th style="width:14%;">Jabatan</th><th style="width:16%;">Waktu</th><th style="width:25%;">Tempat</th></tr>
  @foreach($pad($d['organizations'], 5, 5) as $r)
  <tr>@foreach($r as $cell)<td>{{ $cell }}</td>@endforeach</tr>
  @endforeach
</table>

<div class="h">Riwayat Pekerjaan <i>(dimulai dari pekerjaan sekarang/terakhir)</i></div>
@foreach($d['works'] as $w)
<div class="job">{{ $loop->iteration }}.&nbsp; {{ $w['title'] }}</div>
<table class="j">
  <tr><td class="l">Nama Perusahaan</td><td class="c">:</td><td class="v">{{ $w['company'] }}</td></tr>
  <tr><td class="l">Jenis Usaha</td><td class="c">:</td><td class="v">{{ $w['business'] }}</td></tr>
  <tr><td class="l">Alamat Perusahaan</td><td class="c">:</td><td class="v">{{ $w['address'] }}</td></tr>
  <tr><td class="l">Posisi</td><td class="c">:</td><td class="v">{{ $w['position'] }}</td></tr>
  <tr><td class="l">Lama Bekerja</td><td class="c">:</td><td class="v">Dari: {{ $dots($w['from'], '……… / ……… / ………') }} &nbsp; Sampai: {{ $dots($w['to'], '……… / ……… / ………') }}</td></tr>
  <tr><td class="l">Gaji</td><td class="c">:</td><td class="v">Awal: Rp. {{ $dots($w['salary_start'], '……………………') }} &nbsp; Akhir : Rp. {{ $dots($w['salary_end'], '……………………') }}</td></tr>
  <tr><td class="l">Alasan Berhenti</td><td class="c">:</td><td class="v">{{ $w['reason'] }}</td></tr>
</table>
@endforeach

@if($d['hospital'])
<table class="g" style="margin-top:12px;page-break-inside:avoid;font-size:8pt;">
  <tr>
    <td style="width:50%;height:3.4cm;vertical-align:top;">Jelaskan secara singkat tugas dan tanggung jawab Anda pada pekerjaan sekarang/terakhir<br><br><span style="">{{ $d['works'][0]['job_description'] }}</span></td>
    <td style="vertical-align:top;">Gambarkan posisi Anda di struktur organisasi pekerjaan Anda terakhir bekerja<br><br><span style="">{{ $d['works'][0]['org'] }}</span></td>
  </tr>
</table>
@endif

<div class="h">Referensi <i>(lampirkan no HRD/atasan tempat Anda bekerja sebelumnya)</i></div>
<table class="g">
  <tr><th style="width:17%;">Nama</th><th>Alamat</th><th style="width:16%;">Pekerjaan</th><th style="width:15%;">No HP</th><th style="width:14%;">Hubungan</th></tr>
  @foreach($pad($d['references'], 3, 5) as $r)
  <tr>@foreach($r as $cell)<td>{{ $cell }}</td>@endforeach</tr>
  @endforeach
</table>

@php $i = $d['info']; @endphp
<table class="q" style="margin-top:5px;">
  <tr><td>Apakah Anda keberatan seandainya kami menghubungi nama di atas untuk mendapatkan referensi?<br>
    {{ $box($i['allow_reference_check'] === false) }} Ya &nbsp;&nbsp;&nbsp; {{ $box($i['allow_reference_check'] === true) }} Tidak</td></tr>
  <tr><td>Apakah Anda mempunyai catatan tentang penyakit yang pernah diderita atau apakah Anda sedang dirawat karena suatu penyakit? (termasuk: ayan, hepatitis, tuli, vertigo, covid-19 dan lainnya)<br>
    {{ $box($i['has_disease'] === true) }} Ya &nbsp;&nbsp;&nbsp; {{ $box($i['has_disease'] === false) }} Tidak<br>
    Sebutkan jika ada: {{ $dots($i['disease_notes'], '……………………………………………………………………') }}</td></tr>
  <tr><td style="height:1cm;">Sebutkan aktivitas yang Anda lakukan di waktu luang Anda?<br>{{ $i['hobbies'] }}</td></tr>
  <tr><td style="height:1cm;">Sebutkan bahasa yang Anda kuasai?<br>{{ $i['languages'] }}</td></tr>
  <tr><td style="height:1cm;">Sebutkan benefit dan fasilitas yang Anda peroleh di pekerjaan terakhir/sekarang?<br>{{ $i['last_facilities'] }}</td></tr>
  <tr><td style="height:1cm;">Sebutkan gaji dan fasilitas yang Anda harapkan di {{ $d['company'] }}?<br>{{ $i['expected'] }}</td></tr>
  <tr><td style="height:1cm;">{{ $d['hospital'] ? 'Kapan Anda dapat mulai bekerja di ' . $d['company'] . '?' : 'Kapan Anda dapat mulai bekerja bersama kami?' }}<br>{{ $i['available_start'] }}</td></tr>
</table>

@php $a = $d['assessment']; @endphp
<div class="h">Penilaian Terhadap Diri Sendiri</div>
<div class="ol">
  <div><span class="num">1.</span>&nbsp; Apa-apa saja yang menjadi kebaikan/kelebihan Anda? (Terutama yang berkaitan dengan sifat dan kebiasaan)
    <div class="dl">{{ $a['strengths'] }}</div><div class="dl">&nbsp;</div></div>
  <div><span class="num">2.</span>&nbsp; Apa-apa saja yang menjadi keburukan/kekurangan Anda? (Terutama yang berkaitan dengan sifat dan kebiasaan)
    <div class="dl">{{ $a['weaknesses'] }}</div><div class="dl">&nbsp;</div></div>
  <div style="page-break-inside:avoid;"><span class="num">3.</span>&nbsp; Secara keseluruhan saya merasa:
    @foreach(['very_unsatisfied' => 'a) Sangat tidak puas', 'unsatisfied' => 'b) Tidak puas', 'less_satisfied' => 'c) Agak tidak puas', 'satisfied' => 'd) Cukup puas', 'very_satisfied' => 'e) Sangat puas'] as $k => $text)
    {{-- Pilihan terpilih: huruf (a–e) disilang langsung (X ditumpuk di atas hurufnya), tanpa kotak. --}}
    <div style="margin-left:0.7cm;">@if($a['satisfaction'] === $k)<span>{{ substr($text, 0, 1) }}</span><span style="margin-left:-0.6em;font-size:1.5em;line-height:0.6;vertical-align:-0.18em;font-family:DejaVu Sans;color:#444;font-weight:normal;">&#x2715;</span>@else{{ substr($text, 0, 1) }}@endif{{ substr($text, 1) }}</div>
    @endforeach
    <div style="margin-top:4px;">Alasannya</div>
    <div class="dl">{{ $a['reason'] }}</div><div class="dl">&nbsp;</div></div>
</div>

<p class="stmt">Dengan ini saya menyatakan bahwa informasi tersebut di atas adalah benar. Apabila {{ $d['company'] }} menemukan bahwa informasi yang disampaikan tidak benar, maka {{ $d['company'] }} berhak untuk membatalkan atau melakukan pemutusan hubungan kerja dan bilamana perlu akan diikuti dengan tindakan lainnya.</p>

{{-- RS: ttd di kiri; perusahaan lain (holding): blok ttd di kanan. --}}
<div class="sign" style="margin-left:{{ $d['hospital'] ? '0.7cm' : '10.5cm;margin-top:0.9cm' }};">
  <div>{{ $d['city'] }}, {{ $d['date'] }}</div>
  <div style="height:{{ $d['hospital'] ? '1.3cm' : '2.4cm' }};"></div>
  <div>( {{ $d['name'] }} )</div>
</div>

</div>
</body>
</html>
