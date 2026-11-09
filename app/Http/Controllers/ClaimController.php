<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Claim;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClaimController extends Controller
{
    /**
     * Tampilkan formulir pengajuan klaim untuk barang temuan.
     */
    public function create(Request $request, Item $item): View
    {
        Gate::authorize('create', [Claim::class, $item]);

        $item->load(['category', 'location', 'images']);

        $userLostItems = $request->user()->items()
            ->where('type', 'lost')
            ->where('status', 'open')
            ->orderBy('title')
            ->get();

        return view('klaim.create', [
            'item' => $item,
            'userLostItems' => $userLostItems,
        ]);
    }

    /**
     * Simpan pengajuan klaim beserta bukti kepemilikan.
     */
    public function store(Request $request, Item $item): RedirectResponse
    {
        Gate::authorize('create', [Claim::class, $item]);

        $validated = $request->validate([
            'proof_description' => ['required', 'string', 'min:10'],
            'proof_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'lost_item_id' => [
                'nullable',
                Rule::exists('items', 'item_id')
                    ->where('user_id', $request->user()->user_id)
                    ->where('type', 'lost')
                    ->where('status', 'open'),
            ],
            'declaration' => ['accepted'],
        ], [
            'proof_description.required' => 'Deskripsi bukti kepemilikan wajib diisi.',
            'proof_description.min' => 'Deskripsi bukti kepemilikan minimal 10 karakter.',
            'proof_image.image' => 'Bukti foto harus berupa gambar.',
            'proof_image.mimes' => 'Bukti foto harus berformat JPG atau PNG.',
            'proof_image.max' => 'Ukuran bukti foto maksimal 5 MB.',
            'proof_image.uploaded' => 'Bukti foto gagal diunggah, coba dengan file yang lebih kecil.',
            'lost_item_id.exists' => 'Laporan kehilangan yang dipilih tidak valid atau bukan milik Anda.',
            'declaration.accepted' => 'Anda harus menyatakan kebenaran informasi bukti kepemilikan ini.',
        ]);

        DB::transaction(function () use ($request, $item, $validated): void {
            $imagePath = $request->hasFile('proof_image')
                ? $request->file('proof_image')->store('claims', 'public')
                : null;

            $claim = Claim::create([
                'found_item_id' => $item->item_id,
                'claimant_id' => $request->user()->user_id,
                'lost_item_id' => $validated['lost_item_id'] ?? null,
                'proof_description' => $validated['proof_description'],
                'proof_image' => $imagePath,
                'status' => 'pending',
            ]);

            AuditLog::create([
                'user_id' => $request->user()->user_id,
                'entity_type' => 'claim',
                'entity_id' => $claim->claim_id,
                'action' => 'create',
                'old_values' => null,
                'new_values' => [
                    'found_item_id' => $item->item_id,
                    'lost_item_id' => $claim->lost_item_id,
                    'status' => 'pending',
                ],
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()->route('home')->with('success', 'Pengajuan klaim berhasil dikirim dan sedang menunggu verifikasi.');
    }
}
