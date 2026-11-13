<?php

use App\Models\Claim;
use App\Models\Dispute;
use App\Models\Handover;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to login when visiting the admin dashboard', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('regular users cannot access the admin dashboard', function () {
    $this->actingAs(User::factory()->create(['role' => 'user']))
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin can view the dashboard sections', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Admin')
        ->assertSee('Laporan Kehilangan')
        ->assertSee('Laporan Penemuan')
        ->assertSee('Klaim Menunggu')
        ->assertSee('Banding Terbuka')
        ->assertSee('Serah Terima')
        ->assertSee('Aktivitas Terbaru')
        ->assertSee('Tren Laporan')
        ->assertSee('Laporan per Bulan')
        ->assertSee('Laporan per Status')
        ->assertSee('Belum ada klaim yang menunggu verifikasi.')
        ->assertSee('Belum ada banding yang terbuka.')
        ->assertSee('Belum ada aktivitas.')
        ->assertSee(route('admin.dashboard'), false);
});

test('dashboard stats and pending lists reflect the database', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $claimant = User::factory()->create(['name' => 'Dede Firdaus']);

    $foundItem = Item::factory()->create([
        'user_id' => $admin->user_id,
        'type' => 'found',
        'title' => 'Tas Ransel Eiger',
    ]);
    Item::factory()->create([
        'user_id' => $claimant->user_id,
        'type' => 'lost',
    ]);

    $pendingClaim = Claim::factory()->create([
        'found_item_id' => $foundItem->item_id,
        'claimant_id' => $claimant->user_id,
        'status' => 'pending',
    ]);

    $openDispute = Dispute::factory()->create([
        'claim_id' => $pendingClaim->claim_id,
        'reported_by' => $claimant->user_id,
        'status' => 'open',
    ]);

    Handover::factory()->create([
        'claim_id' => $pendingClaim->claim_id,
        'handed_by' => $admin->user_id,
        'received_by' => $claimant->user_id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('stats', fn (array $stats) => $stats['users'] === 2
            && $stats['lost'] === 1
            && $stats['found'] === 1
            && $stats['claims_pending'] === 1
            && $stats['disputes_open'] === 1
            && $stats['handovers'] === 1)
        ->assertViewHas('pendingClaims', fn ($claims) => $claims->pluck('claim_id')->all() === [$pendingClaim->claim_id])
        ->assertViewHas('openDisputes', fn ($disputes) => $disputes->pluck('dispute_id')->all() === [$openDispute->dispute_id])
        ->assertSee('Tas Ransel Eiger')
        ->assertSee('Dede Firdaus');
});

test('dashboard shows recent activity and monthly trend', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['name' => 'Budi Santoso']);

    Item::factory()->create([
        'user_id' => $user->user_id,
        'type' => 'lost',
        'title' => 'Kunci Motor Beat',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Kunci Motor Beat')
        ->assertSee('Budi Santoso')
        ->assertViewHas('activities', fn ($activities) => collect($activities)->contains(
            fn (array $activity) => $activity['title'] === 'Kunci Motor Beat'
        ))
        ->assertViewHas('trend', fn (array $trend) => count($trend) === 6
            && collect($trend)->sum('count') === 1)
        ->assertViewHas('statusCounts', fn ($counts) => ($counts['open'] ?? 0) === 1);
});

test('admin dashboard does not expose hidden details or storage locations', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Item::factory()->create([
        'title' => 'Tas Ransel Eiger',
        'hidden_detail' => 'Ada stiker biru di dalam saku depan.',
        'storage_location' => 'Loker petugas gedung A',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Tas Ransel Eiger')
        ->assertDontSee('Ada stiker biru di dalam saku depan.')
        ->assertDontSee('Loker petugas gedung A');
});
