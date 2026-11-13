<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PenemuanController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BerandaController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1,register');

    Route::get('/auth/google', [AuthenticatedSessionController::class, 'redirectToGoogle'])->name('google.login');
    Route::get('/auth/google/callback', [AuthenticatedSessionController::class, 'handleGoogleCallback'])->name('google.callback');
});

Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
Route::get('/email/verify/success', [EmailVerificationController::class, 'success'])->name('verification.success');
Route::get('/email/verify/expired', [EmailVerificationController::class, 'expired'])->name('verification.expired');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->name('verification.resend');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan');
    Route::patch('/pengaturan/notifikasi', [PengaturanController::class, 'updateNotifikasi'])->name('pengaturan.notifikasi');
    Route::patch('/pengaturan/password', [PengaturanController::class, 'updatePassword'])->name('pengaturan.password');

    Route::get('/klaim-saya', [ClaimController::class, 'index'])->name('klaim.saya');
    Route::get('/klaim-saya/{claim}/edit', [ClaimController::class, 'edit'])->name('klaim.edit');
    Route::put('/klaim-saya/{claim}', [ClaimController::class, 'update'])->name('klaim.update');
    Route::delete('/klaim-saya/{claim}', [ClaimController::class, 'destroy'])->name('klaim.destroy');

    Route::get('/laporan-saya', [LaporanController::class, 'index'])->name('laporan.saya');
    Route::get('/laporan-saya/{item}/edit', [LaporanController::class, 'edit'])->name('laporan.edit');
    Route::put('/laporan-saya/{item}', [LaporanController::class, 'update'])->name('laporan.update');
    Route::delete('/laporan-saya/{item}', [LaporanController::class, 'destroy'])->name('laporan.destroy');
    Route::patch('/laporan-saya/{item}/tutup', [LaporanController::class, 'close'])->name('laporan.close');

    Route::get('/lapor/kehilangan', [LaporanController::class, 'create'])->name('lapor.kehilangan');
    Route::post('/lapor/kehilangan', [LaporanController::class, 'store']);

    Route::get('/lapor/penemuan', [LaporanController::class, 'createFound'])->name('lapor.penemuan');
    Route::post('/lapor/penemuan', [LaporanController::class, 'storeFound']);

    Route::get('/item/{item}/klaim', [ClaimController::class, 'create'])->name('klaim.create');
    Route::post('/item/{item}/klaim', [ClaimController::class, 'store'])->name('klaim.store');

    Route::get('/item/{item}/penemuan', [PenemuanController::class, 'create'])->name('penemuan.create');
    Route::post('/item/{item}/penemuan', [PenemuanController::class, 'store'])->name('penemuan.store');

    Route::get('/item/{item}/banding', [DisputeController::class, 'create'])->name('banding.create');
    Route::post('/item/{item}/banding', [DisputeController::class, 'store'])->name('banding.store');

    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard')->middleware('can:accessAdmin');
});
