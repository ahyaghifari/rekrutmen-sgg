<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

/** Daftar & masuk khusus dokter: tabel users yang sama, ditandai account_type = 'dokter'. */
class DokterAuthController extends Controller
{
    public function loginForm()
    {
        return view('dokter.masuk');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        if (Auth::user()->account_type !== 'dokter') {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Akun ini bukan akun dokter. Masuk lewat halaman pelamar umum.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dokter.riwayat', absolute: false));
    }

    public function registerForm()
    {
        return view('dokter.daftar');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'nik'      => ['required', 'string', 'size:16', 'unique:'.User::class],
            'phone'    => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([...$data, 'password' => Hash::make($data['password']), 'account_type' => 'dokter']);

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('dokter.lamar');
    }
}
