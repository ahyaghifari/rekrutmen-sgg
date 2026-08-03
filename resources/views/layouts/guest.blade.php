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
<body class="m-0 bg-slate-100">

<div class="sg-login sg-rise">

  {{-- Left: brand panel --}}
  <div class="sg-login-panel relative overflow-hidden flex flex-col text-white" style="background:linear-gradient(150deg,#042c6c 0%,#0b47a1 78%,#B9308C 190%);">
    <div class="absolute w-[420px] h-[420px] rounded-full -left-40 -top-40 pointer-events-none bg-white/[0.06]"></div>
    <div class="absolute w-[250px] h-[250px] rounded-[56px] -right-[90px] bottom-[120px] rotate-[28deg] pointer-events-none bg-white/[0.05]"></div>
    <div class="absolute w-[180px] h-[180px] rounded-full left-20 -bottom-[90px] pointer-events-none bg-magenta/[0.2]"></div>

    <div class="relative z-[1] flex items-center gap-3">
      <img src="/logo-sgg.png" alt="Syifa Global Group" class="h-9 w-auto brightness-0 invert" />
    </div>

    <div class="relative z-[1] mt-[34px] max-w-[460px]">
      <h1 class="m-0 mb-4 leading-[1.15] font-extrabold tracking-[-1.1px]" style="font-size:clamp(26px,4vw,38px);">Satu akun untuk seluruh perjalanan lamaran Anda.</h1>
      <p class="m-0 text-base leading-[1.65] text-white/80">Pantau tahap seleksi, unggah dokumen susulan, dan terima jadwal wawancara di satu tempat.</p>
      <div class="mt-8 flex flex-col gap-[14px]">
        <div class="flex gap-3 items-center"><i class="fa-solid fa-circle-check text-white/70"></i><span class="text-[14.5px] font-semibold">Status lamaran real-time</span></div>
        <div class="flex gap-3 items-center"><i class="fa-solid fa-circle-check text-white/70"></i><span class="text-[14.5px] font-semibold">Dokumen tersimpan, tidak perlu unggah ulang</span></div>
        <div class="flex gap-3 items-center"><i class="fa-solid fa-circle-check text-white/70"></i><span class="text-[14.5px] font-semibold">Notifikasi jadwal wawancara</span></div>
      </div>
    </div>

    <div class="relative z-[1] mt-auto pt-11 text-[12.5px] text-white/[0.62]">Rekrutmen Syifa Global Group tidak memungut biaya dalam bentuk apa pun.</div>
  </div>

  {{-- Right: form area --}}
  <div class="sg-login-form flex items-center justify-center bg-slate-100">
    <div class="w-full max-w-[400px]">
      {{ $slot }}
    </div>
  </div>

</div>

</body>
</html>
