@props(['opening', 'index' => 0, 'hasActiveApp' => false])

@php
    $co    = $opening->company;
    $color = $co?->brand_color ?? '#042c6c';
    $logo  = $co?->logo_path ? asset('storage/'.$co->logo_path) : null;
    [$r,$g,$b] = sscanf($color, '#%02x%02x%02x');
@endphp

<article class="sg-job-card sg-fade" style="--brand:{{ $color }};transition-delay:{{ $index * 0.07 }}s;border-left:3px solid var(--brand);">
  <div class="flex gap-[14px] items-start">
    <div class="w-[58px] h-[58px] shrink-0 rounded-[14px] grid place-items-center overflow-hidden" aria-hidden="true"
         style="background:rgba({{ $r }},{{ $g }},{{ $b }},0.10);">
      @if($logo)
        <img src="{{ $logo }}" alt="{{ $co->name }}" class="w-full h-full object-contain p-[8px]">
      @else
        <i class="fa-solid {{ sgJobIcon($opening->title) }}" style="color:{{ $color }};font-size:22px;"></i>
      @endif
    </div>
    <div class="flex-1 min-w-0">
      <div class="flex flex-wrap gap-1.5 items-center">
        <h3 class="m-0 text-[15.5px] font-bold text-slate-800 leading-[1.3]">{{ $opening->title }}</h3>
        <span class="px-[9px] py-[3px] rounded-full text-[11px] font-bold whitespace-nowrap"
              style="background:rgba({{ $r }},{{ $g }},{{ $b }},0.10);color:{{ $color }};">Full Time</span>
      </div>
      <div class="mt-1 text-[13px] font-semibold text-slate-600">{{ $co?->name ?? $opening->company_name }}</div>
    </div>
  </div>

  @if($opening->description)
  <div class="mt-3 text-[13px] text-slate-500 leading-normal line-clamp-2">{{ $opening->description }}</div>
  @endif

  <div class="sg-job-card-footer">
    <span class="text-[12.5px] text-slate-500">
      <i class="fa-solid fa-users text-slate-400 mr-[5px]" aria-hidden="true"></i>Kuota: {{ $opening->quota }} orang
    </span>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('lowongan.show', $opening) }}" class="sg-job-card-btn"
         style="background:rgba({{ $r }},{{ $g }},{{ $b }},0.10);color:{{ $color }};">Detail</a>
      @if($hasActiveApp)
        <span class="sg-job-card-btn" style="background:#e2e8f0;color:#94a3b8;cursor:not-allowed;">Lamar</span>
      @else
        <a href="{{ route('lamaran.create', $opening) }}" class="sg-job-card-btn"
           style="background:{{ $color }};color:#fff;">Lamar</a>
      @endif
    </div>
  </div>
</article>
