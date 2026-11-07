<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Tampilkan halaman pendaftaran.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Daftarkan pengguna baru lalu arahkan ke halaman aktivasi email.
     *
     * Pengguna tidak langsung masuk — akun aktif setelah email diverifikasi.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'regex:/@([\w-]+\.)*\bunsika\.ac\.id$/i'],
            // 'email' => ['required', 'email', 'max:255', ''],
            'password' => ['required', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.regex' => 'Email harus menggunakan domain unsika.ac.id.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan serta kebijakan privasi.',
        ]);

        $existing = User::withTrashed()->where('email', $validated['email'])->first();

        if ($existing !== null && ($existing->trashed() || $existing->hasVerifiedEmail())) {
            throw ValidationException::withMessages([
                'email' => 'Email sudah terdaftar.',
            ]);
        }

        if ($existing !== null) {
            return $this->continueUnverifiedRegistration($request, $existing, $validated);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'nim_nip' => User::nimNipFromEmail($validated['email']),
        ]);

        $user->sendEmailVerificationNotification();

        $request->session()->put('pending_verification_email', $validated['email']);

        return redirect()->route('verification.notice');
    }

    /**
     * Timpa data akun yang belum diverifikasi lalu kirim ulang email aktivasi.
     *
     * @param  array<string, mixed>  $validated
     */
    private function continueUnverifiedRegistration(Request $request, User $user, array $validated): RedirectResponse
    {
        $user->forceFill([
            'name' => $validated['name'],
            'password' => $validated['password'],
        ])->save();

        if ($user->verificationCooldownRemaining() === 0) {
            $user->sendEmailVerificationNotification();
        }

        $request->session()->put('pending_verification_email', $validated['email']);

        return redirect()->route('verification.notice');
    }
}
