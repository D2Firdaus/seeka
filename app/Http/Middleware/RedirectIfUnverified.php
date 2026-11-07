<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfUnverified
{
    /**
     * Tahan pengguna yang belum aktivasi email dari aksi yang butuh verifikasi.
     *
     * Browsing (beranda, dll) tetap boleh; verifikasi, logout, dan register
     * dikecualikan agar tidak redirect loop.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null
            && ! $user->hasVerifiedEmail()
            && ! $request->routeIs('verification.*', 'logout', 'home')
            && ! $request->is('register', 'register/*')
        ) {
            return Redirect::route('verification.notice');
        }

        return $next($request);
    }
}
