<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\KeputusanBandingMail;
use App\Mail\KeputusanKlaimMail;
use App\Models\AuditLog;
use App\Models\Claim;
use App\Models\Dispute;
use App\Models\ItemLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    /**
     * Tampilkan antrean verifikasi klaim untuk petugas.
     */
    public function klaim(Request $request): View
    {
        Gate::authorize('viewAny', Claim::class);

        $statuses = match ($request->input('status', 'pending')) {
            'all' => [],
            'approved' => ['approved'],
            'rejected' => ['rejected'],
            default => ['pending'],
        };

        $requestedPerPage = $request->integer('per_page', 9);
        $perPage = in_array($requestedPerPage, [9, 18, 36], true) ? $requestedPerPage : 9;

        $claims = Claim::query()
            ->with(['foundItem.category', 'foundItem.images', 'claimant'])
            ->when($request->filled('q'), fn ($query) => $query->whereHas('foundItem', fn ($itemQuery) => $itemQuery->where('title', 'like', '%'.$request->string('q').'%')))
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->latest()
            ->paginate($perPage)
            ->appends(array_filter($request->query(), fn ($value) => ! blank($value)));

        return view('verifikasi.klaim', [
            'claims' => $claims,
        ]);
    }

    /**
     * Tampilkan detail klaim beserta ciri khusus barang untuk pencocokan petugas.
     */
    public function showKlaim(Claim $claim): View
    {
        Gate::authorize('viewAny', Claim::class);

        $claim->load(['foundItem.category', 'foundItem.location', 'foundItem.images', 'claimant', 'lostItem', 'verifiedBy']);

        return view('verifikasi.klaim-show', [
            'claim' => $claim,
        ]);
    }

    /**
     * Setujui klaim, kunci barang menjadi milik klaim yang disetujui, dan tolak klaim lainnya.
     */
    public function setujuiKlaim(Request $request, Claim $claim): RedirectResponse
    {
        Gate::authorize('verify', $claim);

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'admin_note.max' => 'Catatan petugas maksimal 1000 karakter.',
        ]);

        $claim->load(['claimant', 'foundItem']);

        if ($claim->foundItem->status !== 'open') {
            return redirect()->route('admin.verifikasi.klaim.show', $claim)->withErrors([
                'admin_note' => 'Barang tidak lagi dalam status terbuka sehingga klaim tidak dapat disetujui.',
            ]);
        }

        $otherClaims = $claim->foundItem->claims()
            ->with('claimant')
            ->where('status', 'pending')
            ->where('claim_id', '!=', $claim->claim_id)
            ->get();

        DB::transaction(function () use ($request, $claim, $otherClaims, $validated): void {
            $claim->update([
                'status' => 'approved',
                'verified_by' => $request->user()->user_id,
                'verified_at' => now(),
                'admin_note' => $validated['admin_note'] ?? null,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->user_id,
                'entity_type' => 'claim',
                'entity_id' => $claim->claim_id,
                'action' => 'approve',
                'old_values' => ['status' => 'pending'],
                'new_values' => [
                    'status' => 'approved',
                    'admin_note' => $validated['admin_note'] ?? null,
                ],
                'ip_address' => $request->ip(),
            ]);

            foreach ($otherClaims as $otherClaim) {
                $otherClaim->update([
                    'status' => 'rejected',
                    'verified_by' => $request->user()->user_id,
                    'verified_at' => now(),
                    'admin_note' => 'Klaim lain untuk barang ini telah disetujui petugas.',
                ]);

                AuditLog::create([
                    'user_id' => $request->user()->user_id,
                    'entity_type' => 'claim',
                    'entity_id' => $otherClaim->claim_id,
                    'action' => 'reject',
                    'old_values' => ['status' => 'pending'],
                    'new_values' => [
                        'status' => 'rejected',
                        'admin_note' => 'Klaim lain untuk barang ini telah disetujui petugas.',
                    ],
                    'ip_address' => $request->ip(),
                ]);
            }

            $item = $claim->foundItem;

            $item->update(['status' => 'claimed']);

            ItemLog::create([
                'item_id' => $item->item_id,
                'user_id' => $request->user()->user_id,
                'old_status' => 'open',
                'new_status' => 'claimed',
                'note' => 'Klaim disetujui petugas.',
                'ip_address' => $request->ip(),
            ]);
        });

        if ($claim->claimant->notify_email) {
            Mail::to($claim->claimant->email)->send(new KeputusanKlaimMail($claim, 'approved', $validated['admin_note'] ?? null));
        }

        foreach ($otherClaims as $otherClaim) {
            if ($otherClaim->claimant->notify_email) {
                Mail::to($otherClaim->claimant->email)->send(new KeputusanKlaimMail($otherClaim, 'rejected', $otherClaim->admin_note));
            }
        }

        return redirect()->route('admin.verifikasi.klaim')->with('success', 'Klaim berhasil disetujui.');
    }

    /**
     * Tolak klaim yang bukti kepemilikannya tidak cocok dengan ciri khusus barang.
     */
    public function tolakKlaim(Request $request, Claim $claim): RedirectResponse
    {
        Gate::authorize('verify', $claim);

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'admin_note.required' => 'Catatan petugas wajib diisi sebagai alasan penolakan.',
            'admin_note.min' => 'Catatan petugas minimal 10 karakter.',
            'admin_note.max' => 'Catatan petugas maksimal 1000 karakter.',
        ]);

        $claim->load(['claimant', 'foundItem']);

        DB::transaction(function () use ($request, $claim, $validated): void {
            $claim->update([
                'status' => 'rejected',
                'verified_by' => $request->user()->user_id,
                'verified_at' => now(),
                'admin_note' => $validated['admin_note'],
            ]);

            AuditLog::create([
                'user_id' => $request->user()->user_id,
                'entity_type' => 'claim',
                'entity_id' => $claim->claim_id,
                'action' => 'reject',
                'old_values' => ['status' => 'pending'],
                'new_values' => [
                    'status' => 'rejected',
                    'admin_note' => $validated['admin_note'],
                ],
                'ip_address' => $request->ip(),
            ]);
        });

        if ($claim->claimant->notify_email) {
            Mail::to($claim->claimant->email)->send(new KeputusanKlaimMail($claim, 'rejected', $validated['admin_note']));
        }

        return redirect()->route('admin.verifikasi.klaim')->with('success', 'Klaim berhasil ditolak.');
    }

    /**
     * Tampilkan antrean banding untuk petugas.
     */
    public function banding(Request $request): View
    {
        Gate::authorize('viewAny', Dispute::class);

        $statuses = match ($request->input('status', 'open')) {
            'all' => [],
            'resolved' => ['resolved'],
            'dismissed' => ['dismissed'],
            default => ['open'],
        };

        $requestedPerPage = $request->integer('per_page', 9);
        $perPage = in_array($requestedPerPage, [9, 18, 36], true) ? $requestedPerPage : 9;

        $disputes = Dispute::query()
            ->with(['claim.foundItem.category', 'claim.foundItem.images', 'reportedBy'])
            ->when($request->filled('q'), fn ($query) => $query->whereHas('claim.foundItem', fn ($itemQuery) => $itemQuery->where('title', 'like', '%'.$request->string('q').'%')))
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->latest()
            ->paginate($perPage)
            ->appends(array_filter($request->query(), fn ($value) => ! blank($value)));

        return view('verifikasi.banding', [
            'disputes' => $disputes,
        ]);
    }

    /**
     * Tampilkan detail banding beserta klaim yang dibandingkan untuk pemeriksaan petugas.
     */
    public function showBanding(Dispute $dispute): View
    {
        Gate::authorize('viewAny', Dispute::class);

        $dispute->load(['claim.foundItem.category', 'claim.foundItem.location', 'claim.foundItem.images', 'claim.claimant', 'claim.verifiedBy', 'reportedBy', 'resolvedBy']);

        return view('verifikasi.banding-show', [
            'dispute' => $dispute,
        ]);
    }

    /**
     * Terima banding: cabut klaim terdahulu dan kembalikan status barang menjadi terbuka.
     */
    public function terimaBanding(Request $request, Dispute $dispute): RedirectResponse
    {
        Gate::authorize('resolve', $dispute);

        $validated = $request->validate([
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'resolution_note.max' => 'Catatan petugas maksimal 1000 karakter.',
        ]);

        $dispute->load(['claim.claimant', 'claim.foundItem', 'reportedBy']);

        if ($dispute->claim->status !== 'approved') {
            return redirect()->route('admin.verifikasi.banding.show', $dispute)->withErrors([
                'resolution_note' => 'Klaim terkait sudah tidak disetujui sehingga banding tidak dapat diterima.',
            ]);
        }

        $claim = $dispute->claim;
        $item = $claim->foundItem;
        $oldItemStatus = $item->status;

        DB::transaction(function () use ($request, $dispute, $claim, $item, $oldItemStatus, $validated): void {
            $dispute->update([
                'status' => 'resolved',
                'resolution_note' => $validated['resolution_note'] ?? null,
                'resolved_by' => $request->user()->user_id,
                'resolved_at' => now(),
            ]);

            $claim->update([
                'status' => 'rejected',
                'verified_by' => $request->user()->user_id,
                'verified_at' => now(),
                'admin_note' => 'Klaim dicabut karena banding dari pihak lain diterima petugas.',
            ]);

            $item->update(['status' => 'open']);

            ItemLog::create([
                'item_id' => $item->item_id,
                'user_id' => $request->user()->user_id,
                'old_status' => $oldItemStatus,
                'new_status' => 'open',
                'note' => 'Banding diterima petugas, klaim dicabut.',
                'ip_address' => $request->ip(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->user_id,
                'entity_type' => 'dispute',
                'entity_id' => $dispute->dispute_id,
                'action' => 'resolve',
                'old_values' => ['status' => 'open'],
                'new_values' => [
                    'status' => 'resolved',
                    'resolution_note' => $validated['resolution_note'] ?? null,
                ],
                'ip_address' => $request->ip(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->user_id,
                'entity_type' => 'claim',
                'entity_id' => $claim->claim_id,
                'action' => 'reject',
                'old_values' => ['status' => 'approved'],
                'new_values' => [
                    'status' => 'rejected',
                    'admin_note' => 'Klaim dicabut karena banding dari pihak lain diterima petugas.',
                ],
                'ip_address' => $request->ip(),
            ]);
        });

        if ($dispute->reportedBy->notify_email) {
            Mail::to($dispute->reportedBy->email)->send(new KeputusanBandingMail($dispute, 'resolved', $validated['resolution_note'] ?? null));
        }

        if ($claim->claimant->notify_email) {
            Mail::to($claim->claimant->email)->send(new KeputusanKlaimMail($claim, 'revoked', 'Klaim dicabut karena banding dari pihak lain diterima petugas.'));
        }

        return redirect()->route('admin.verifikasi.banding')->with('success', 'Banding diterima, klaim terdahulu dicabut dan status barang kembali terbuka.');
    }

    /**
     * Tolak banding yang buktinya tidak cukup kuat; keputusan klaim yang berlaku tetap.
     */
    public function tolakBanding(Request $request, Dispute $dispute): RedirectResponse
    {
        Gate::authorize('resolve', $dispute);

        $validated = $request->validate([
            'resolution_note' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'resolution_note.required' => 'Catatan petugas wajib diisi sebagai alasan penolakan.',
            'resolution_note.min' => 'Catatan petugas minimal 10 karakter.',
            'resolution_note.max' => 'Catatan petugas maksimal 1000 karakter.',
        ]);

        $dispute->load(['claim.foundItem', 'reportedBy']);

        DB::transaction(function () use ($request, $dispute, $validated): void {
            $dispute->update([
                'status' => 'dismissed',
                'resolution_note' => $validated['resolution_note'],
                'resolved_by' => $request->user()->user_id,
                'resolved_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->user_id,
                'entity_type' => 'dispute',
                'entity_id' => $dispute->dispute_id,
                'action' => 'dismiss',
                'old_values' => ['status' => 'open'],
                'new_values' => [
                    'status' => 'dismissed',
                    'resolution_note' => $validated['resolution_note'],
                ],
                'ip_address' => $request->ip(),
            ]);
        });

        if ($dispute->reportedBy->notify_email) {
            Mail::to($dispute->reportedBy->email)->send(new KeputusanBandingMail($dispute, 'dismissed', $validated['resolution_note']));
        }

        return redirect()->route('admin.verifikasi.banding')->with('success', 'Banding berhasil ditolak.');
    }
}
