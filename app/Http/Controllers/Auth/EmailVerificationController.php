<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    /**
     * Tampilkan halaman menunggu aktivasi email.
     */
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        return view('auth.verify-email');
    }

    /**
     * Aktivasi email dari link bertanda tangan yang dikirim ke email.
     */
    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! hash_equals((string) $user->getKey(), (string) $request->route('id'))
            || ! hash_equals($user->verificationHash(), (string) $request->route('hash'))) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            event(new Verified($user));
        }

        return redirect()->route('verification.success');
    }

    /**
     * Kirim ulang link aktivasi ke email pengguna.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        if ($user->verificationCooldownRemaining() === 0) {
            $user->sendEmailVerificationNotification();
        }

        return back();
    }

    /**
     * Tampilkan halaman berhasil diaktivasi.
     */
    public function success(Request $request): View|RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return view('auth.verify-success');
    }
}
