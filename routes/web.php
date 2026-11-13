<?php

use App\Http\Controllers\Admin\VerifikasiController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MetadataController;
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

    Route::middleware('can:accessAdmin')->group(function () {
        Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');
        Route::get('/admin/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
        Route::patch('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

        Route::get('/admin/metadata/kategori', [MetadataController::class, 'kategoriIndex'])->name('admin.metadata.kategori');
        Route::get('/admin/metadata/kategori/create', [MetadataController::class, 'kategoriCreate'])->name('admin.metadata.kategori.create');
        Route::post('/admin/metadata/kategori', [MetadataController::class, 'kategoriStore'])->name('admin.metadata.kategori.store');
        Route::get('/admin/metadata/kategori/{category}/edit', [MetadataController::class, 'kategoriEdit'])->name('admin.metadata.kategori.edit');
        Route::put('/admin/metadata/kategori/{category}', [MetadataController::class, 'kategoriUpdate'])->name('admin.metadata.kategori.update');
        Route::delete('/admin/metadata/kategori/{category}', [MetadataController::class, 'kategoriDestroy'])->name('admin.metadata.kategori.destroy');

        Route::get('/admin/metadata/lokasi', [MetadataController::class, 'lokasiIndex'])->name('admin.metadata.lokasi');
        Route::get('/admin/metadata/lokasi/create', [MetadataController::class, 'lokasiCreate'])->name('admin.metadata.lokasi.create');
        Route::post('/admin/metadata/lokasi', [MetadataController::class, 'lokasiStore'])->name('admin.metadata.lokasi.store');
        Route::get('/admin/metadata/lokasi/{location}/edit', [MetadataController::class, 'lokasiEdit'])->name('admin.metadata.lokasi.edit');
        Route::put('/admin/metadata/lokasi/{location}', [MetadataController::class, 'lokasiUpdate'])->name('admin.metadata.lokasi.update');
        Route::delete('/admin/metadata/lokasi/{location}', [MetadataController::class, 'lokasiDestroy'])->name('admin.metadata.lokasi.destroy');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/verifikasi/klaim', [VerifikasiController::class, 'klaim'])->name('verifikasi.klaim');
        Route::get('/verifikasi/klaim/{claim}', [VerifikasiController::class, 'showKlaim'])->name('verifikasi.klaim.show');
        Route::patch('/verifikasi/klaim/{claim}/setujui', [VerifikasiController::class, 'setujuiKlaim'])->name('verifikasi.klaim.setujui');
        Route::patch('/verifikasi/klaim/{claim}/tolak', [VerifikasiController::class, 'tolakKlaim'])->name('verifikasi.klaim.tolak');

        Route::get('/verifikasi/banding', [VerifikasiController::class, 'banding'])->name('verifikasi.banding');
        Route::get('/verifikasi/banding/{dispute}', [VerifikasiController::class, 'showBanding'])->name('verifikasi.banding.show');
        Route::patch('/verifikasi/banding/{dispute}/terima', [VerifikasiController::class, 'terimaBanding'])->name('verifikasi.banding.terima');
        Route::patch('/verifikasi/banding/{dispute}/tolak', [VerifikasiController::class, 'tolakBanding'])->name('verifikasi.banding.tolak');
    });
});
