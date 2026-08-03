<x-guest-layout>

  <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2 mb-[26px] px-[14px] py-2 rounded-[10px] border border-slate-200 bg-white text-slate-600 text-[13px] font-bold no-underline hover:border-slate-300 transition-colors duration-150">
    <i class="fa-solid fa-arrow-left text-[11px]"></i> Kembali ke beranda
  </a>

  <h2 class="m-0 mb-1.5 text-[26px] font-extrabold text-slate-800 tracking-[-0.7px]">Masuk ke akun kandidat</h2>
  <p class="m-0 mb-7 text-sm text-slate-500">Gunakan email yang terdaftar saat membuat akun.</p>

  <div class="bg-white border border-slate-200 rounded-[20px] p-7 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-[18px]">
      @csrf

      {{-- Email --}}
      <div>
        <label for="email" class="block mb-[7px] text-[13px] font-bold text-slate-700">Email</label>
        <div class="flex items-center gap-[10px] border {{ $errors->has('email') ? 'border-red-600' : 'border-slate-200' }} rounded-xl px-[14px] bg-white focus-within:border-navy transition-colors duration-150">
          <i class="fa-regular fa-envelope text-slate-400 text-sm"></i>
          <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
            placeholder="nama@email.com"
            class="flex-1 border-0 outline-none py-[13px] text-sm text-slate-800 bg-transparent" />
        </div>
        @error('email')<p class="mt-1.5 text-[12.5px] text-red-600">{{ $message }}</p>@enderror
      </div>

      {{-- Password --}}
      <div>
        <div class="flex items-center justify-between mb-[7px]">
          <label for="password" class="text-[13px] font-bold text-slate-700">Kata Sandi</label>
          @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="text-[12.5px] font-bold text-navy">Lupa sandi?</a>
          @endif
        </div>
        <div class="flex items-center gap-[10px] border border-slate-200 rounded-xl px-[14px] bg-white focus-within:border-navy transition-colors duration-150">
          <i class="fa-solid fa-lock text-slate-400 text-[13px]"></i>
          <input id="password" type="password" name="password" required autocomplete="current-password"
            placeholder="••••••••"
            class="flex-1 border-0 outline-none py-[13px] text-sm text-slate-800 bg-transparent" />
        </div>
        @error('password')<p class="mt-1.5 text-[12.5px] text-red-600">{{ $message }}</p>@enderror
      </div>

      {{-- Remember me --}}
      <label class="flex items-center gap-[9px] text-[13.5px] text-slate-600 font-semibold cursor-pointer">
        <input type="checkbox" name="remember" class="w-4 h-4 accent-navy cursor-pointer" />
        Ingat saya di perangkat ini
      </label>

      {{-- Submit --}}
      <button type="submit" class="w-full py-[14px] rounded-xl border-0 bg-navy hover:bg-navy-dark text-white text-[14.5px] font-bold cursor-pointer transition-colors duration-150">Masuk</button>

      <div class="flex items-center gap-3">
        <div class="flex-1 h-px bg-slate-200"></div>
        <span class="text-xs font-semibold text-slate-500">atau</span>
        <div class="flex-1 h-px bg-slate-200"></div>
      </div>

      <a href="{{ route('register') }}" class="w-full py-[13px] rounded-xl border border-slate-300 bg-white text-slate-700 text-sm font-bold text-center no-underline block hover:border-navy hover:text-navy transition-colors duration-150">Daftar sebagai kandidat baru</a>
    </form>
  </div>

  <p class="mt-[22px] text-[12.5px] leading-[1.6] text-slate-500 text-center">Dengan masuk, Anda menyetujui <a href="#" class="font-bold">Syarat &amp; Ketentuan</a> serta <a href="#" class="font-bold">Kebijakan Privasi</a> Syifa Global Group.</p>

</x-guest-layout>
