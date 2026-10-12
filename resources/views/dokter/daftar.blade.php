<x-dokter.auth-shell title="Daftar Akun Dokter" subtitle="Buat akun untuk melamar sebagai dokter di Syifa Global Group.">
  <form method="POST" action="{{ route('dokter.daftar') }}" class="flex flex-col gap-5">
    @csrf
    <x-dokter.input name="name" label="Nama lengkap" autofocus autocomplete="name" placeholder="Nama sesuai KTP" />
    <x-dokter.input name="email" label="Email" type="email" autocomplete="username" placeholder="nama@email.com" />
    <x-dokter.input name="nik" label="NIK (KTP)" inputmode="numeric" maxlength="16" pattern="\d{16}" autocomplete="off" placeholder="16 digit sesuai KTP" />
    <x-dokter.input name="phone" label="No. HP / WhatsApp" type="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" />
    <x-dokter.input name="password" label="Kata sandi" type="password" autocomplete="new-password" />
    <x-dokter.input name="password_confirmation" label="Ulangi kata sandi" type="password" autocomplete="new-password" />
    <button type="submit" class="w-full py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold transition-colors">Daftar</button>
  </form>
  <p class="mt-5 mb-0 text-center text-sm text-slate-500">Sudah punya akun? <a href="{{ route('dokter.masuk') }}" class="font-bold text-teal-700 hover:underline">Masuk</a></p>
</x-dokter.auth-shell>
