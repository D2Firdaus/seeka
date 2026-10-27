<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Socialite\Socialite;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    /**
     * Tampilkan halaman masuk.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Autentikasi pengguna berdasarkan kredensial yang diberikan.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi yang Anda masukkan salah.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * Alihkan pengguna ke halaman masuk Google.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Terima callback OAuth Google lalu autentikasi penggunanya.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors([
                'email' => 'Gagal masuk dengan Google. Silakan coba lagi.',
            ]);
        }

        if ($googleUser->getEmail() === null) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Google Anda tidak memiliki email yang valid.',
            ]);
        }

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::withTrashed()->where('email', $googleUser->getEmail())->first();

        if ($user?->trashed()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun dengan email ini telah dinonaktifkan.',
            ]);
        }

        $user ??= User::create([
            'name' => $googleUser->getName() ?: 'Pengguna',
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'password' => Str::random(32),
        ]);

        if ($user->google_id !== $googleUser->getId()) {
            $user->update(['google_id' => $googleUser->getId()]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }
}
