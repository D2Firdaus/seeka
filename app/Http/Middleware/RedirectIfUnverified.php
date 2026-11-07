<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfUnverified
{
    /**
     * Tahan pengguna yang sudah masuk tetapi belum aktivasi email di halaman verifikasi.
     *
     * Tamu tetap bebas menjelajah; rute verifikasi, logout, dan register dikecualikan
     * agar tidak redirect loop atau menghalangi pendaftaran.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null
            && ! $user->hasVerifiedEmail()
            && ! $request->routeIs('verification.*', 'logout')
            && ! $request->is('register', 'register/*')
        ) {
            return Redirect::route('verification.notice');
        }

        return $next($request);
    }
}
