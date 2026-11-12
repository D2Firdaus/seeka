<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil milik pengguna yang sedang masuk.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        $user->load([
            'items' => fn ($query) => $query->with(['category', 'images'])->latest('created_at')->limit(3),
            'claims' => fn ($query) => $query->with('foundItem')->latest('created_at')->limit(3),
            'disputesReported' => fn ($query) => $query->with('claim.foundItem')->latest('created_at')->limit(3),
        ]);

        $stats = [
            'lostReports' => $user->items()->where('type', 'lost')->count(),
            'foundReports' => $user->items()->where('type', 'found')->count(),
            'claims' => $user->claims()->count(),
            'pendingClaims' => $user->claims()->where('status', 'pending')->count(),
            'handoversGiven' => $user->handoversGiven()->count(),
            'handoversReceived' => $user->handoversReceived()->count(),
        ];

        return view('profil.show', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    /**
     * Simpan perubahan nama, nomor HP, dan NIM/NIP pengguna.
     *
     * Email dan peran sengaja tidak diekspos ke form. NIM/NIP hanya boleh
     * diisi lewat sini selama masih kosong — setelah terisi menjadi readonly
     * (nilai awalnya diturunkan dari email saat registrasi).
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s-]{6,20}$/'],
        ];

        $messages = [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'phone.max' => 'Nomor HP maksimal 20 karakter.',
            'phone.regex' => 'Format nomor HP tidak valid. Gunakan angka, misalnya 6281234567890.',
        ];

        if (blank($user->nim_nip)) {
            $rules['nim_nip'] = [
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9]{6,30}$/',
                Rule::unique('users', 'nim_nip')->ignore($user),
            ];

            $messages += [
                'nim_nip.max' => 'NIM/NIP maksimal 30 karakter.',
                'nim_nip.regex' => 'NIM/NIP hanya boleh berisi angka, panjang 6 sampai 30 digit.',
                'nim_nip.unique' => 'NIM/NIP tersebut sudah dipakai pengguna lain.',
            ];
        }

        $validated = $request->validate($rules, $messages);

        $data = [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ];

        if (blank($user->nim_nip)) {
            $data['nim_nip'] = $validated['nim_nip'] ?? null;
        }

        $user->update($data);

        return redirect()->route('profile.show')->with('success', 'Perubahan profil berhasil disimpan.');
    }
}
