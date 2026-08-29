<x-guest-layout>

  <a href="{{ route('login') }}" class="sg-auth-back inline-flex items-center gap-2 mb-[26px] px-[14px] py-2 rounded-[10px] border border-slate-200 bg-white text-slate-600 text-[13px] font-bold no-underline hover:border-slate-300 transition-colors duration-150">
    <i class="fa-solid fa-arrow-left text-[11px]"></i> Kembali ke masuk
  </a>

  <h2 class="m-0 mb-1.5 text-[26px] font-extrabold text-slate-800 tracking-[-0.7px]">Lupa kata sandi?</h2>
  <p class="m-0 mb-7 text-sm text-slate-500">Masukkan email yang terdaftar, kami akan kirimkan tautan untuk membuat kata sandi baru.</p>

  <div class="sg-card rounded-[20px] p-7 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-[18px]">
      @csrf

      {{-- Email --}}
      <div>
        <label for="email" class="block mb-[7px] text-[13px] font-bold text-slate-700">Email</label>
        <div class="sg-input-wrap flex items-center border {{ $errors->has('email') ? 'border-red-500' : 'border-slate-200' }} rounded-[13px] overflow-hidden transition-colors duration-150 focus-within:border-navy focus-within:ring-2 focus-within:ring-navy/10">
          <div class="sg-input-icon flex items-center justify-center w-[46px] shrink-0 self-stretch">
            <i class="fa-regular fa-envelope text-[13px] text-slate-400"></i>
          </div>
          <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
            placeholder="nama@email.com"
            class="flex-1 border-0 outline-none py-[14px] pr-[14px] text-[13.5px] text-slate-800 bg-transparent" />
        </div>
        @error('email')<p class="mt-1.5 text-[12px] text-red-500 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation text-[11px]"></i>{{ $message }}</p>@enderror
      </div>

      {{-- Submit --}}
      <button type="submit" class="w-full py-[14px] rounded-xl border-0 bg-navy hover:bg-navy-dark text-white text-[14.5px] font-bold cursor-pointer transition-colors duration-150">Kirim Tautan Reset Kata Sandi</button>
    </form>
  </div>

</x-guest-layout>
