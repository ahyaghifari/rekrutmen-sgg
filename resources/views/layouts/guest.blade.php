<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Rekrutmen — Syifa Global Group' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="m-0">

<div class="sg-login sg-rise">

  {{-- Left: brand panel --}}
  <div class="sg-login-panel relative overflow-hidden flex flex-col text-white" style="background:linear-gradient(150deg,#042c6c 0%,#0b47a1 78%,#B9308C 190%);">
    <div class="absolute w-[420px] h-[420px] rounded-full -left-40 -top-40 pointer-events-none bg-white/[0.06]"></div>
    <div class="absolute w-[250px] h-[250px] rounded-[56px] -right-[90px] bottom-[120px] rotate-[28deg] pointer-events-none bg-white/[0.05]"></div>
    <div class="absolute w-[180px] h-[180px] rounded-full left-20 -bottom-[90px] pointer-events-none bg-magenta/[0.2]"></div>

    <div class="relative z-[1] flex items-center gap-3">
      <img src="/logo-sgg.png" alt="Syifa Global Group" class="h-9 w-auto brightness-0 invert" />
    </div>

    @php $guestCompanies = \App\Models\Company::all(); @endphp
    <div class="relative z-[1] mt-[34px] max-w-[460px]">
      <h1 class="m-0 mb-4 leading-[1.15] font-extrabold tracking-[-1.1px]" style="font-size:clamp(26px,4vw,38px);">Satu akun untuk seluruh perjalanan lamaran Anda.</h1>
      <p class="m-0 text-base leading-[1.65] text-white/80">Bergabunglah bersama keluarga besar Syifa Global Group dan mulai karier yang bermakna.</p>
      <div class="mt-8">
        <div class="text-[11px] font-bold tracking-[0.1em] uppercase text-white/50 mb-4">Grup perusahaan</div>
        <div class="flex flex-wrap gap-3">
          @foreach($guestCompanies as $gc)
            <div class="flex items-center gap-2 px-3 py-[7px] rounded-[10px] bg-white/[0.12]">
              @if($gc->logo_path)
                <img src="{{ asset('storage/'.$gc->logo_path) }}" alt="{{ $gc->name }}" class="h-5 w-auto object-contain brightness-0 invert opacity-90">
              @else
                <span class="text-[12px] font-bold text-white/80">{{ $gc->name }}</span>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="relative z-[1] mt-auto pt-11 text-[12.5px] text-white/[0.62]">Rekrutmen Syifa Global Group tidak memungut biaya dalam bentuk apa pun.</div>
  </div>

  {{-- Right: form area --}}
  <div class="sg-login-form relative flex items-center justify-center">
    <div class="w-full max-w-[400px]">
      {{ $slot }}
    </div>
  </div>

</div>

</body>
</html>
