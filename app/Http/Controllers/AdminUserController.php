<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     * Tampilkan daftar pengguna untuk dikelola admin.
     */
    public function index(Request $request): View
    {
        $role = in_array($request->input('role'), ['admin', 'user'], true) ? $request->input('role') : null;

        $users = User::query()
            ->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request) {
                $search = '%'.$request->string('q').'%';
                $query->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('nim_nip', 'like', $search);
            }))
            ->when($role !== null, fn ($query) => $query->where('role', $role))
            ->latest()
            ->paginate(10)
            ->appends(array_filter($request->query(), fn ($value) => ! blank($value)));

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    /**
     * Tampilkan form tambah pengguna.
     */
    public function create(): View
    {
        return view('admin.users.create');
    }

    /**
     * Simpan pengguna baru beserta peran dan kata sandinya.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'nim_nip' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]{6,30}$/', Rule::unique('users', 'nim_nip')],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s-]{6,20}$/'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah terdaftar.',
            'nim_nip.max' => 'NIM/NIP maksimal 30 karakter.',
            'nim_nip.regex' => 'NIM/NIP hanya boleh berisi angka, panjang 6 sampai 30 digit.',
            'nim_nip.unique' => 'NIM/NIP tersebut sudah dipakai pengguna lain.',
            'phone.max' => 'Nomor HP maksimal 20 karakter.',
            'phone.regex' => 'Format nomor HP tidak valid. Gunakan angka, misalnya 6281234567890.',
            'role.required' => 'Peran wajib dipilih.',
            'role.in' => 'Peran tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        User::forceCreate([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nim_nip' => $validated['nim_nip'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Tampilkan form edit pengguna.
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Perbarui data pengguna beserta peran dan kata sandinya (opsional).
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'nim_nip' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]{6,30}$/', Rule::unique('users', 'nim_nip')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s-]{6,20}$/'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah terdaftar.',
            'nim_nip.max' => 'NIM/NIP maksimal 30 karakter.',
            'nim_nip.regex' => 'NIM/NIP hanya boleh berisi angka, panjang 6 sampai 30 digit.',
            'nim_nip.unique' => 'NIM/NIP tersebut sudah dipakai pengguna lain.',
            'phone.max' => 'Nomor HP maksimal 20 karakter.',
            'phone.regex' => 'Format nomor HP tidak valid. Gunakan angka, misalnya 6281234567890.',
            'role.required' => 'Peran wajib dipilih.',
            'role.in' => 'Peran tidak valid.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        if ($validated['role'] !== $user->role) {
            Gate::authorize('updateRole', $user);
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nim_nip' => $validated['nim_nip'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
        ];

        if (filled($validated['password'] ?? null)) {
            $data['password'] = $validated['password'];
        }

        $user->forceFill($data)->save();

        return redirect()->route('admin.users')->with('success', 'Pengguna berhasil diperbarui.');
    }

    /**
     * Hapus akun pengguna lain (soft delete).
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'Pengguna berhasil dihapus.');
    }
}
