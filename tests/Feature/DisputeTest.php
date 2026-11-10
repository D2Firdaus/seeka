<?php

use App\Models\Category;
use App\Models\Claim;
use App\Models\Dispute;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to login when visiting the dispute form', function () {
    $item = Item::factory()->create(['status' => 'claimed']);
    Claim::factory()->approved()->create(['found_item_id' => $item->item_id]);

    $this->get(route('banding.create', $item))
        ->assertRedirect(route('login'));
});

test('users cannot dispute an open item', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['status' => 'open']);

    $this->actingAs($user)
        ->get(route('banding.create', $item))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('banding.store', $item), [
            'reason' => 'Saya merasa ini barang saya yang hilang.',
            'declaration' => '1',
        ])
        ->assertForbidden();
});

test('users cannot dispute a closed item', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['status' => 'closed']);

    $this->actingAs($user)
        ->get(route('banding.create', $item))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('banding.store', $item), [
            'reason' => 'Saya merasa ini barang saya yang hilang.',
            'declaration' => '1',
        ])
        ->assertForbidden();
});

test('users cannot dispute an item with no claim records', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['status' => 'claimed']);

    $this->actingAs($user)
        ->get(route('banding.create', $item))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('banding.store', $item), [
            'reason' => 'Saya merasa ini barang saya yang hilang.',
            'declaration' => '1',
        ])
        ->assertForbidden();
});

test('claimant cannot dispute an item approved for themselves', function () {
    $claimant = User::factory()->create();
    $item = Item::factory()->create(['status' => 'claimed']);
    Claim::factory()->approved()->create([
        'found_item_id' => $item->item_id,
        'claimant_id' => $claimant->user_id,
    ]);

    $this->actingAs($claimant)
        ->get(route('banding.create', $item))
        ->assertForbidden();

    $this->actingAs($claimant)
        ->post(route('banding.store', $item), [
            'reason' => 'Mencoba mengajukan banding pada barang klaim saya sendiri.',
            'declaration' => '1',
        ])
        ->assertForbidden();
});

test('users cannot submit multiple open disputes for the same item', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['status' => 'claimed']);
    $claim = Claim::factory()->approved()->create(['found_item_id' => $item->item_id]);

    Dispute::factory()->create([
        'claim_id' => $claim->claim_id,
        'reported_by' => $user->user_id,
        'status' => 'open',
    ]);

    $this->actingAs($user)
        ->get(route('banding.create', $item))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('banding.store', $item), [
            'reason' => 'Pengajuan banding kedua saya.',
            'declaration' => '1',
        ])
        ->assertForbidden();
});

test('user can view dispute form for claimed item', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Aksesoris']);
    $location = Location::factory()->create(['name' => 'Kantin Gedung C']);

    $item = Item::factory()->create([
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'title' => 'Jam Tangan G-Shock',
        'status' => 'claimed',
    ]);

    Claim::factory()->approved()->create(['found_item_id' => $item->item_id]);

    $this->actingAs($user)
        ->get(route('banding.create', $item))
        ->assertOk()
        ->assertSee('Ajukan Banding')
        ->assertSee('Jam Tangan G-Shock')
        ->assertSee('Aksesoris')
        ->assertSee('Kantin Gedung C')
        ->assertSee('Diklaim');
});

test('user can view dispute form for returned item', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create([
        'title' => 'Kamera Canon M50',
        'status' => 'returned',
    ]);

    Claim::factory()->approved()->create(['found_item_id' => $item->item_id]);

    $this->actingAs($user)
        ->get(route('banding.create', $item))
        ->assertOk()
        ->assertSee('Dikembalikan');
});

test('user can submit a valid dispute and records audit trail', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create([
        'title' => 'Laptop Asus ROG',
        'status' => 'claimed',
    ]);

    $claim = Claim::factory()->approved()->create(['found_item_id' => $item->item_id]);

    $response = $this->actingAs($user)
        ->post(route('banding.store', $item), [
            'reason' => 'Saya memiliki nomor seri laptop dan kartu garansi resmi atas nama saya yang sesuai.',
            'declaration' => '1',
        ]);

    $response->assertRedirect(route('home'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('disputes', [
        'claim_id' => $claim->claim_id,
        'reported_by' => $user->user_id,
        'status' => 'open',
        'reason' => 'Saya memiliki nomor seri laptop dan kartu garansi resmi atas nama saya yang sesuai.',
    ]);

    $dispute = Dispute::where('claim_id', $claim->claim_id)->first();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->user_id,
        'entity_type' => 'dispute',
        'entity_id' => $dispute->dispute_id,
        'action' => 'create',
    ]);
});

test('dispute submission fails when reason is missing or too short', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['status' => 'claimed']);
    Claim::factory()->approved()->create(['found_item_id' => $item->item_id]);

    $this->actingAs($user)
        ->post(route('banding.store', $item), [
            'reason' => 'pendek',
            'declaration' => '1',
        ])
        ->assertSessionHasErrors('reason');

    $this->assertDatabaseEmpty('disputes');
});

test('dispute submission fails when declaration is missing', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['status' => 'claimed']);
    Claim::factory()->approved()->create(['found_item_id' => $item->item_id]);

    $this->actingAs($user)
        ->post(route('banding.store', $item), [
            'reason' => 'Ini barang saya yang hilang minggu lalu.',
        ])
        ->assertSessionHasErrors('declaration');

    $this->assertDatabaseEmpty('disputes');
});

test('card and modal CTA point to banding form when user is authenticated for claimed or returned items', function (string $status) {
    $user = User::factory()->create();
    $item = Item::factory()->create([
        'title' => 'Dompet Kulit Coklat',
        'status' => $status,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('banding.create', $item));
})->with(['claimed', 'returned']);

test('dispute form renders item detail modal without action buttons', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create([
        'title' => 'Helm KYT Putih',
        'status' => 'claimed',
    ]);

    Claim::factory()->approved()->create(['found_item_id' => $item->item_id]);

    $response = $this->actingAs($user)
        ->get(route('banding.create', $item))
        ->assertOk();

    $response->assertDontSee('Merasa ini barang Anda?');
});
