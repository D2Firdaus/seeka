<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\ItemMatch;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PenemuanPolicy
{
    /**
     * Tentukan apakah pengguna dapat mengajukan penemuan untuk barang kehilangan tertentu.
     */
    public function create(User $user, Item $item): Response
    {
        if ($item->type !== 'lost') {
            return Response::deny('Hanya barang kehilangan yang dapat diajukan penemuan.');
        }

        if ($item->status !== 'open') {
            return Response::deny('Barang ini sudah tidak dalam status terbuka untuk penemuan.');
        }

        if ($item->user_id === $user->user_id) {
            return Response::deny('Anda tidak dapat mengajukan penemuan untuk barang yang Anda laporkan sendiri.');
        }

        $hasExistingMatch = ItemMatch::query()
            ->where('lost_item_id', $item->item_id)
            ->whereHas('foundItem', fn ($query) => $query->where('user_id', $user->user_id))
            ->whereIn('status', ['new', 'candidate', 'notified'])
            ->exists();

        if ($hasExistingMatch) {
            return Response::deny('Anda sudah pernah mengajukan penemuan untuk barang kehilangan ini.');
        }

        return Response::allow();
    }
}
