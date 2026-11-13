<?php

namespace App\Policies;

use App\Models\Dispute;
use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DisputePolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar banding (admin).
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail banding.
     */
    public function view(User $user, Dispute $dispute): bool
    {
        return $user->user_id === $dispute->reported_by || $user->role === 'admin';
    }

    /**
     * Tentukan apakah petugas dapat memutuskan (menerima/menolak) banding tertentu.
     */
    public function resolve(User $user, Dispute $dispute): Response
    {
        if ($user->role !== 'admin') {
            return Response::deny('Hanya petugas yang dapat memutuskan banding.');
        }

        if ($dispute->reported_by === $user->user_id) {
            return Response::deny('Anda tidak dapat memutuskan banding yang Anda ajukan sendiri.');
        }

        if ($dispute->status !== 'open') {
            return Response::deny('Banding yang sudah diputuskan tidak dapat diproses kembali.');
        }

        return Response::allow();
    }

    /**
     * Tentukan apakah pengguna dapat mengajukan banding untuk barang tertentu.
     */
    public function create(User $user, Item $item): Response
    {
        if ($item->status === 'closed') {
            return Response::deny('Laporan barang ini sudah ditutup.');
        }

        if (! in_array($item->status, ['claimed', 'returned'], true)) {
            return Response::deny('Barang ini belum diklaim atau dikembalikan sehingga tidak dapat diajukan banding.');
        }

        $claim = $item->claims()->where('status', 'approved')->first() ?? $item->claims()->latest()->first();

        if (! $claim) {
            return Response::deny('Tidak ada data klaim yang dapat diajukan banding untuk barang ini.');
        }

        if ($claim->claimant_id === $user->user_id) {
            return Response::deny('Anda tidak dapat mengajukan banding atas barang yang disetujui untuk klaim Anda sendiri.');
        }

        $hasOpenDispute = Dispute::query()
            ->where('claim_id', $claim->claim_id)
            ->where('reported_by', $user->user_id)
            ->where('status', 'open')
            ->exists();

        if ($hasOpenDispute) {
            return Response::deny('Anda sudah memiliki pengajuan banding yang sedang diproses untuk barang ini.');
        }

        return Response::allow();
    }
}
