<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    /**
     * Tampilkan halaman pengaturan akun milik pengguna yang sedang masuk.
     */
    public function edit(Request $request): View
    {
        return view('pengaturan.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Simpan preferensi notifikasi (email dan WhatsApp) pengguna.
     */
    public function updateNotifikasi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notify_email' => ['required', 'boolean'],
            'notify_whatsapp' => ['required', 'boolean'],
        ], [
            'notify_email.required' => 'Preferensi notifikasi email wajib dipilih.',
            'notify_email.boolean' => 'Preferensi notifikasi email tidak valid.',
            'notify_whatsapp.required' => 'Preferensi notifikasi WhatsApp wajib dipilih.',
            'notify_whatsapp.boolean' => 'Preferensi notifikasi WhatsApp tidak valid.',
        ]);

        $request->user()->update([
            'notify_email' => (bool) $validated['notify_email'],
            'notify_whatsapp' => (bool) $validated['notify_whatsapp'],
        ]);

        return redirect()->route('pengaturan')->with('success', 'Preferensi notifikasi berhasil disimpan.');
    }

    /**
     * Ganti kata sandi pengguna setelah kata sandi saat ini diverifikasi.
     *
     * Akun yang masuk lewat Google tidak punya kata sandi lokal, jadi tidak
     * diizinkan mengubah kata sandi dari halaman ini.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (filled($user->google_id)) {
            return redirect()->route('pengaturan')->withErrors([
                'password' => 'Kata sandi hanya dapat diubah untuk akun dengan login email.',
            ]);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user->update(['password' => $validated['password']]);

        return redirect()->route('pengaturan')->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
