<x-dokter.auth-shell title="Masuk Dokter" subtitle="Masuk untuk melanjutkan lamaran Anda.">
  <form method="POST" action="{{ route('dokter.masuk') }}" class="flex flex-col gap-5">
    @csrf
    <x-dokter.input name="email" label="Email" type="email" autofocus autocomplete="username" />
    <x-dokter.input name="password" label="Kata sandi" type="password" autocomplete="current-password" />
    <div class="flex items-center justify-between text-sm">
      <label class="flex items-center gap-2 text-slate-600"><input type="checkbox" name="remember" class="rounded border-2 border-slate-300 text-teal-600 focus:ring-teal-600"> Ingat saya</label>
      <a href="{{ route('password.request') }}" class="font-semibold text-teal-700 hover:underline">Lupa sandi?</a>
    </div>
    <button type="submit" class="w-full py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold transition-colors">Masuk</button>
  </form>
  <p class="mt-5 mb-0 text-center text-sm text-slate-500">Belum punya akun? <a href="{{ route('dokter.daftar') }}" class="font-bold text-teal-700 hover:underline">Daftar</a></p>
</x-dokter.auth-shell>
