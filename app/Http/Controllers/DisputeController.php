<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Dispute;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DisputeController extends Controller
{
    /**
     * Tampilkan formulir pengajuan banding untuk barang yang sudah diklaim/dikembalikan.
     */
    public function create(Request $request, Item $item): View
    {
        Gate::authorize('create', [Dispute::class, $item]);

        $item->load(['category', 'location', 'images']);

        $claim = $item->claims()->where('status', 'approved')->first()
            ?? $item->claims()->latest()->firstOrFail();

        return view('banding.create', [
            'item' => $item,
            'claim' => $claim,
        ]);
    }

    /**
     * Simpan pengajuan banding beserta alasan dan pernyataan.
     */
    public function store(Request $request, Item $item): RedirectResponse
    {
        Gate::authorize('create', [Dispute::class, $item]);

        $claim = $item->claims()->where('status', 'approved')->first()
            ?? $item->claims()->latest()->firstOrFail();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10'],
            'declaration' => ['accepted'],
        ], [
            'reason.required' => 'Alasan pengajuan banding wajib diisi.',
            'reason.min' => 'Alasan pengajuan banding minimal 10 karakter.',
            'declaration.accepted' => 'Anda harus menyatakan kebenaran informasi pengajuan banding ini.',
        ]);

        DB::transaction(function () use ($request, $item, $claim, $validated): void {
            $dispute = Dispute::create([
                'claim_id' => $claim->claim_id,
                'reported_by' => $request->user()->user_id,
                'reason' => $validated['reason'],
                'status' => 'open',
            ]);

            AuditLog::create([
                'user_id' => $request->user()->user_id,
                'entity_type' => 'dispute',
                'entity_id' => $dispute->dispute_id,
                'action' => 'create',
                'old_values' => null,
                'new_values' => [
                    'item_id' => $item->item_id,
                    'claim_id' => $claim->claim_id,
                    'reason' => $validated['reason'],
                    'status' => 'open',
                ],
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()->route('home')->with('success', 'Pengajuan banding berhasil dikirim dan akan segera diperiksa oleh petugas.');
    }
}
