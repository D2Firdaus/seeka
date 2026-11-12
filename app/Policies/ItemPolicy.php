<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ItemPolicy
{
    /**
     * Tentukan apakah pengguna dapat mengedit laporan barangnya sendiri.
     */
    public function update(User $user, Item $item): Response
    {
        if ($item->user_id !== $user->user_id) {
            return Response::deny('Anda hanya dapat mengubah laporan yang Anda buat sendiri.');
        }

        if ($item->status !== 'open') {
            return Response::deny('Laporan yang sudah tidak berstatus terbuka tidak dapat diubah.');
        }

        return Response::allow();
    }

    /**
     * Tentukan apakah pengguna dapat menutup laporan barangnya sendiri.
     */
    public function close(User $user, Item $item): Response
    {
        if ($item->user_id !== $user->user_id) {
            return Response::deny('Anda hanya dapat menutup laporan yang Anda buat sendiri.');
        }

        if ($item->status !== 'open') {
            return Response::deny('Hanya laporan berstatus terbuka yang dapat ditutup.');
        }

        return Response::allow();
    }

    /**
     * Tentukan apakah pengguna dapat menghapus laporan barangnya sendiri.
     */
    public function delete(User $user, Item $item): Response
    {
        if ($item->user_id !== $user->user_id) {
            return Response::deny('Anda hanya dapat menghapus laporan yang Anda buat sendiri.');
        }

        if ($item->status !== 'open') {
            return Response::deny('Laporan yang sudah tidak berstatus terbuka tidak dapat dihapus.');
        }

        return Response::allow();
    }
}
