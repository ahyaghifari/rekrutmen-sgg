<x-app-layout>
<x-slot name="title">Lamaran Saya</x-slot>

@php
$statusMap = [
    'applied'     => ['label' => 'Lamaran Masuk',        'badge' => 'badge-applied'],
    'screening'   => ['label' => 'Peninjauan Berkas',    'badge' => 'badge-screening'],
    'shortlisted' => ['label' => 'Lolos Seleksi Berkas', 'badge' => 'badge-interview'],
    'interview'   => ['label' => 'Wawancara',            'badge' => 'badge-interview'],
    'passed'      => ['label' => 'Diterima',             'badge' => 'badge-passed'],
    'rejected'    => ['label' => 'Tidak Dilanjutkan',    'badge' => 'badge-rejected'],
];
$needsQuiz = $applications->first(function ($a) {
    if ($a->status !== 'shortlisted' || ! $a->quiz_sent_at || $a->quiz_result) {
        return false;
    }
    $total = $a->jobOpening->quizzes->count();
    $selesai = $a->quizAttempts->where('status', 'submitted')->count();
    return ! ($total > 0 && $selesai >= $total);
});
@endphp

<div style="margin:0 auto;padding:20px 16px;">

  {{-- Header --}}
  <div style="margin-bottom:16px;">
    <h1 class="text-slate-800" style="font-size:20px;font-weight:800;margin:0 0 2px;">Lamaran Saya</h1>
    <p class="text-slate-500" style="font-size:13px;margin:0;">Seluruh riwayat lamaran yang pernah kamu kirimkan.</p>
  </div>

  @if($needsQuiz)
  <a href="{{ route('tes-online.show') }}" style="text-decoration:none;display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:14px;margin-bottom:16px;background:rgba(4,44,108,.06);border:1px solid rgba(4,44,108,.15);">
    <div style="flex-shrink:0;width:36px;height:36px;border-radius:10px;background:#042c6c;display:flex;align-items:center;justify-content:center;">
      <i class="fa-solid fa-laptop" style="color:#fff;font-size:14px;"></i>
    </div>
    <div style="flex:1;min-width:0;">
      <div class="text-slate-800" style="font-size:13.5px;font-weight:700;">Tes online sudah bisa dikerjakan</div>
      <div class="text-slate-500" style="font-size:12px;margin-top:1px;">{{ $needsQuiz->jobOpening->title }}</div>
    </div>
    <span style="flex-shrink:0;padding:8px 14px;background:#042c6c;color:#fff;border-radius:9px;font-size:12.5px;font-weight:700;">Laksanakan Tes</span>
  </a>
  @endif

  @if ($applications->isEmpty())
    {{-- Empty state --}}
    <div class="sg-card" style="border-radius:16px;padding:36px 24px;text-align:center;">
      <div style="width:48px;height:48px;border-radius:50%;background:rgba(4,44,108,.07);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
        <i class="fa-solid fa-file-circle-question" style="font-size:20px;color:#042c6c;"></i>
      </div>
      <div class="text-slate-800" style="font-size:14px;font-weight:700;margin-bottom:4px;">Belum ada lamaran</div>
      <p class="text-slate-400" style="font-size:12.5px;margin:0 0 18px;">Kamu belum pernah melamar posisi apapun.</p>
      <a href="{{ route('dashboard') }}#lowongan" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;background:#042c6c;color:#fff;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;">
        <i class="fa-solid fa-briefcase"></i> Lihat Lowongan
      </a>
    </div>

  @else
    {{-- Stats row --}}
    @php
      $active = $applications->filter(fn($a) => in_array($a->status, ['applied','screening','interview']))->count();
      $passed = $applications->where('status','passed')->count();
    @endphp
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;">
      <div class="sg-stat-card">
        <div class="text-slate-400" style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Total</div>
        <div style="font-size:22px;font-weight:800;color:#042c6c;">{{ $applications->count() }}</div>
      </div>
      <div class="sg-stat-card">
        <div class="text-slate-400" style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Aktif</div>
        <div style="font-size:22px;font-weight:800;color:#0284c7;">{{ $active }}</div>
      </div>
      <div class="sg-stat-card">
        <div class="text-slate-400" style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Diterima</div>
        <div style="font-size:22px;font-weight:800;color:#047C54;">{{ $passed }}</div>
      </div>
    </div>

    {{-- Application list --}}
    <div style="display:flex;flex-direction:column;gap:8px;">
      @foreach ($applications as $app)
        @php $s = $statusMap[$app->status] ?? ['label' => $app->status, 'badge' => 'badge-applied']; @endphp
        <a href="{{ route('lamaran.show', $app) }}" style="display:block;text-decoration:none;">
          <div class="sg-list-item">
            <div style="flex-shrink:0;width:38px;height:38px;border-radius:10px;background:rgba(4,44,108,.06);display:flex;align-items:center;justify-content:center;">
              <i class="fa-solid fa-briefcase" style="color:#042c6c;font-size:14px;"></i>
            </div>
            <div style="flex:1;min-width:0;">
              <div class="text-slate-800" style="font-size:14px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $app->jobOpening->title }}</div>
              <div class="text-slate-500" style="font-size:12px;margin-top:1px;">
                {{ $app->jobOpening->company_name }}
                <span style="margin:0 5px;color:#cbd5e1;">·</span>
                {{ $app->created_at->translatedFormat('d F Y') }}
              </div>
            </div>
            <div style="flex-shrink:0;display:flex;align-items:center;gap:10px;">
              <span class="badge {{ $s['badge'] }}">{{ $s['label'] }}</span>
              <i class="fa-solid fa-chevron-right" style="font-size:10px;color:#cbd5e1;"></i>
            </div>
          </div>
        </a>
      @endforeach
    </div>
  @endif

</div>
</x-app-layout>
