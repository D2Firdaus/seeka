<?php

namespace App\Policies;

use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClaimPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar klaim (admin).
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail klaim.
     */
    public function view(User $user, Claim $claim): bool
    {
        return $user->user_id === $claim->claimant_id || $user->role === 'admin';
    }

    /**
     * Tentukan apakah pengguna dapat mengajukan klaim untuk barang temuan tertentu.
     */
    public function create(User $user, Item $item): Response
    {
        if ($item->type !== 'found') {
            return Response::deny('Hanya barang temuan yang dapat diajukan klaim.');
        }

        if ($item->status !== 'open') {
            return Response::deny('Barang ini sudah tidak dalam status terbuka untuk klaim.');
        }

        if ($item->user_id === $user->user_id) {
            return Response::deny('Anda tidak dapat mengklaim barang yang Anda laporkan sendiri.');
        }

        $hasPendingClaim = Claim::query()
            ->where('found_item_id', $item->item_id)
            ->where('claimant_id', $user->user_id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingClaim) {
            return Response::deny('Anda sudah memiliki pengajuan klaim yang sedang diproses untuk barang ini.');
        }

        return Response::allow();
    }

    /**
     * Tentukan apakah pengguna dapat mengubah klaimnya sendiri.
     */
    public function update(User $user, Claim $claim): Response
    {
        if ($claim->claimant_id !== $user->user_id) {
            return Response::deny('Anda hanya dapat mengubah klaim yang Anda ajukan sendiri.');
        }

        if ($claim->status !== 'pending') {
            return Response::deny('Klaim yang sudah diproses petugas tidak dapat diubah.');
        }

        return Response::allow();
    }

    /**
     * Tentukan apakah pengguna dapat menarik (menghapus) klaimnya sendiri.
     */
    public function delete(User $user, Claim $claim): Response
    {
        if ($claim->claimant_id !== $user->user_id) {
            return Response::deny('Anda hanya dapat menarik klaim yang Anda ajukan sendiri.');
        }

        if ($claim->status !== 'pending') {
            return Response::deny('Klaim yang sudah diproses petugas tidak dapat ditarik.');
        }

        return Response::allow();
    }
}
