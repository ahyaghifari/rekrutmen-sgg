{{-- Isi kartu biodata di dashboard: teks progres + tombol. Dipakai di kartu jadwal wawancara dan kartu mandiri. --}}
<div class="text-[13px]" style="color:{{ $tlBrand }};">
  <strong>{{ $activeApp->status === 'interview' ? 'Lengkapi biodata sebelum wawancara.' : 'Lengkapi biodata Anda.' }}</strong>
  @if($bioList)
    <span>{{ $bioDone }} dari {{ count($bioList) }} data wajib terisi.</span>
  @endif
  <div class="mt-2 flex flex-wrap gap-2 {{ $align ?? '' }}">
    <a href="{{ route('data-diri.biodata') }}" target="_blank" rel="noopener" class="sg-job-card-btn" style="background:{{ $tlBrand }}1a;color:{{ $tlBrand }};">Pratinjau PDF</a>
    <a href="{{ route('data-diri.biodata.unduh') }}" class="sg-job-card-btn" style="background:{{ $tlBrand }}1a;color:{{ $tlBrand }};">Unduh PDF</a>
    <a href="{{ route('data-diri.edit') }}" class="sg-job-card-btn" style="background:{{ $tlBrand }};">{{ $bioList && $bioDone === count($bioList) ? 'Perbarui Biodata' : 'Isi Biodata' }}</a>
  </div>
</div>
