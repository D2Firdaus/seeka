<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    /**
     * Tampilkan halaman menunggu aktivasi email.
     *
     * Halaman ini bisa diakses tanpa login — email diambil dari session.
     */
    public function notice(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('pending_verification_email');

        if ($email === null) {
            return redirect()->route('register');
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            return redirect()->route('register');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login');
        }

        return view('auth.verify-email', [
            'email' => $email,
            'cooldown' => $user->verificationCooldownRemaining(),
        ]);
    }

    /**
     * Aktivasi email dari link bertanda tangan lalu masukkan pengguna.
     */
    public function verify(Request $request): RedirectResponse
    {
        $user = User::find($request->route('id'));

        if ($user === null
            || ! $request->hasValidSignature()
            || ! hash_equals($user->verificationHash(), (string) $request->route('hash'))) {
            return redirect()->route('verification.expired');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            event(new Verified($user));
        }

        $request->session()->forget('pending_verification_email');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.success');
    }

    /**
     * Kirim ulang link aktivasi ke email pengguna.
     */
    public function resend(Request $request): RedirectResponse
    {
        $email = $request->session()->get('pending_verification_email');

        if ($email === null) {
            return redirect()->route('register');
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            return redirect()->route('register');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login');
        }

        if ($user->verificationCooldownRemaining() === 0) {
            $user->sendEmailVerificationNotification();
        }

        return redirect()->route('verification.notice');
    }

    /**
     * Tampilkan halaman berhasil diaktivasi.
     */
    public function success(Request $request): View|RedirectResponse
    {
        return view('auth.verify-success');
    }

    /**
     * Tampilkan halaman link verifikasi kedaluwarsa.
     */
    public function expired(Request $request): View|RedirectResponse
    {
        return view('auth.verify-expired');
    }
}
