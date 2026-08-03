<x-guest-layout>
<x-slot name="title">Daftar Akun — Syifa Global Group</x-slot>

  <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2 mb-[26px] px-[14px] py-2 rounded-[10px] border border-slate-200 bg-white text-slate-600 text-[13px] font-bold no-underline hover:border-slate-300 transition-colors duration-150">
    <i class="fa-solid fa-arrow-left text-[11px]"></i> Kembali ke beranda
  </a>

  <h2 class="m-0 mb-1.5 text-[26px] font-extrabold text-slate-800 tracking-[-0.7px]">Buat Akun Kandidat</h2>
  <p class="m-0 mb-7 text-sm text-slate-500">Daftar gratis dan mulai melamar di Syifa Global Group.</p>

  <div class="bg-white border border-slate-200 rounded-[20px] p-7 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-[18px]">
      @csrf

      {{-- Name --}}
      <div>
        <label for="name" class="block mb-[7px] text-[13px] font-bold text-slate-700">Nama Lengkap <span class="text-red-700">*</span></label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
          placeholder="Nama sesuai KTP"
          class="w-full box-border border {{ $errors->has('name') ? 'border-red-600' : 'border-slate-200' }} rounded-xl py-3 px-3.5 text-sm text-slate-800 outline-none bg-white focus:border-navy transition-colors duration-150" />
        @error('name')<p class="mt-1.5 text-[12.5px] text-red-600">{{ $message }}</p>@enderror
      </div>

      {{-- Email --}}
      <div>
        <label for="email" class="block mb-[7px] text-[13px] font-bold text-slate-700">Email <span class="text-red-700">*</span></label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
          placeholder="nama@email.com"
          class="w-full box-border border {{ $errors->has('email') ? 'border-red-600' : 'border-slate-200' }} rounded-xl py-3 px-3.5 text-sm text-slate-800 outline-none bg-white focus:border-navy transition-colors duration-150" />
        @error('email')<p class="mt-1.5 text-[12.5px] text-red-600">{{ $message }}</p>@enderror
      </div>

      {{-- Password --}}
      <div>
        <label for="password" class="block mb-[7px] text-[13px] font-bold text-slate-700">Kata Sandi <span class="text-red-700">*</span></label>
        <input id="password" type="password" name="password" required autocomplete="new-password"
          placeholder="Min. 8 karakter"
          class="w-full box-border border {{ $errors->has('password') ? 'border-red-600' : 'border-slate-200' }} rounded-xl py-3 px-3.5 text-sm text-slate-800 outline-none bg-white focus:border-navy transition-colors duration-150" />
        @error('password')<p class="mt-1.5 text-[12.5px] text-red-600">{{ $message }}</p>@enderror
      </div>

      {{-- Password confirmation --}}
      <div>
        <label for="password_confirmation" class="block mb-[7px] text-[13px] font-bold text-slate-700">Konfirmasi Kata Sandi <span class="text-red-700">*</span></label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
          placeholder="Ulangi kata sandi"
          class="w-full box-border border border-slate-200 rounded-xl py-3 px-3.5 text-sm text-slate-800 outline-none bg-white focus:border-navy transition-colors duration-150" />
        @error('password_confirmation')<p class="mt-1.5 text-[12.5px] text-red-600">{{ $message }}</p>@enderror
      </div>

      <button type="submit" class="w-full py-[14px] rounded-xl border-0 bg-navy hover:bg-navy-dark text-white text-[14.5px] font-bold cursor-pointer transition-colors duration-150">Buat Akun</button>

      <div class="flex items-center gap-3">
        <div class="flex-1 h-px bg-slate-200"></div>
        <span class="text-xs font-semibold text-slate-500">sudah punya akun?</span>
        <div class="flex-1 h-px bg-slate-200"></div>
      </div>

      <a href="{{ route('login') }}" class="w-full py-[13px] rounded-xl border border-slate-300 bg-white text-slate-700 text-sm font-bold text-center no-underline block hover:border-navy hover:text-navy transition-colors duration-150">Masuk ke akun yang ada</a>
    </form>
  </div>

  <p class="mt-[22px] text-[12.5px] leading-[1.6] text-slate-500 text-center">Dengan mendaftar, Anda menyetujui <a href="#" class="font-bold">Syarat &amp; Ketentuan</a> serta <a href="#" class="font-bold">Kebijakan Privasi</a> Syifa Global Group.</p>

</x-guest-layout>
