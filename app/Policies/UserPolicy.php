<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Tentukan apakah pengguna dapat mengubah peran pengguna lain.
     */
    public function updateRole(User $user, User $target): Response
    {
        if ($user->role !== 'admin') {
            return Response::deny('Hanya admin yang dapat mengelola pengguna.');
        }

        if ($target->user_id === $user->user_id) {
            return Response::deny('Anda tidak dapat mengubah peran Anda sendiri.');
        }

        return Response::allow();
    }

    /**
     * Tentukan apakah pengguna dapat menghapus akun pengguna lain.
     */
    public function delete(User $user, User $target): Response
    {
        if ($user->role !== 'admin') {
            return Response::deny('Hanya admin yang dapat mengelola pengguna.');
        }

        if ($target->user_id === $user->user_id) {
            return Response::deny('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        return Response::allow();
    }
}
