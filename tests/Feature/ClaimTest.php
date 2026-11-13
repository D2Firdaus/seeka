<?php

use App\Models\Category;
use App\Models\Claim;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fakeClaimPhoto(string $name): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

    return UploadedFile::fake()->createWithContent($name, $png);
}

test('guests are redirected to login when visiting the claim form', function () {
    $item = Item::factory()->create(['type' => 'found', 'status' => 'open']);

    $this->get(route('klaim.create', $item))
        ->assertRedirect(route('login'));
});

test('users cannot claim a lost item', function () {
    $user = User::factory()->create();
    $lostItem = Item::factory()->create(['type' => 'lost', 'status' => 'open']);

    $this->actingAs($user)
        ->get(route('klaim.create', $lostItem))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('klaim.store', $lostItem), [
            'proof_description' => 'Ini dompet saya yang hilang kemarin.',
            'declaration' => '1',
        ])
        ->assertForbidden();
});

test('users cannot claim an item that is not open', function (string $status) {
    $user = User::factory()->create();
    $item = Item::factory()->create(['type' => 'found', 'status' => $status]);

    $this->actingAs($user)
        ->get(route('klaim.create', $item))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('klaim.store', $item), [
            'proof_description' => 'Ini barang saya yang hilang.',
            'declaration' => '1',
        ])
        ->assertForbidden();
})->with(['claimed', 'returned', 'closed']);

test('users cannot claim an item they reported themselves', function () {
    $reporter = User::factory()->create();
    $item = Item::factory()->create([
        'user_id' => $reporter->user_id,
        'type' => 'found',
        'status' => 'open',
    ]);

    $this->actingAs($reporter)
        ->get(route('klaim.create', $item))
        ->assertForbidden();

    $this->actingAs($reporter)
        ->post(route('klaim.store', $item), [
            'proof_description' => 'Ini barang saya sendiri.',
            'declaration' => '1',
        ])
        ->assertForbidden();
});

test('users cannot submit multiple pending claims for the same item', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['type' => 'found', 'status' => 'open']);

    Claim::factory()->create([
        'found_item_id' => $item->item_id,
        'claimant_id' => $user->user_id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('klaim.create', $item))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('klaim.store', $item), [
            'proof_description' => 'Pengajuan klaim kedua saya.',
            'declaration' => '1',
        ])
        ->assertForbidden();
});

test('user can view the claim form for an open found item', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Elektronik']);
    $location = Location::factory()->create(['name' => 'Perpustakaan']);

    $item = Item::factory()->create([
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'type' => 'found',
        'status' => 'open',
        'title' => 'Earphone Bluetooth Hitam',
    ]);

    $this->actingAs($user)
        ->get(route('klaim.create', $item))
        ->assertOk()
        ->assertSee('Ajukan Klaim')
        ->assertSee('Earphone Bluetooth Hitam')
        ->assertSee('Elektronik')
        ->assertSee('Perpustakaan');
});

test('claim form lists the claimants open lost reports for linking', function () {
    $user = User::factory()->create();
    $foundItem = Item::factory()->create(['type' => 'found', 'status' => 'open']);

    $userLostItem = Item::factory()->create([
        'user_id' => $user->user_id,
        'type' => 'lost',
        'status' => 'open',
        'title' => 'Kunci Motor Beat Saya',
    ]);

    $otherUserLostItem = Item::factory()->create([
        'type' => 'lost',
        'status' => 'open',
        'title' => 'Kunci Motor Orang Lain',
    ]);

    $this->actingAs($user)
        ->get(route('klaim.create', $foundItem))
        ->assertOk()
        ->assertSee('Kunci Motor Beat Saya')
        ->assertDontSee('Kunci Motor Orang Lain');
});

test('claimant can submit a valid claim and records audit trail', function () {
    $claimant = User::factory()->create();
    $item = Item::factory()->create([
        'type' => 'found',
        'status' => 'open',
        'title' => 'Tas Ransel Eiger',
    ]);

    $response = $this->actingAs($claimant)
        ->post(route('klaim.store', $item), [
            'proof_description' => 'Di dalam tas ada kartu mahasiswa atas nama Dede Firdaus dan dompet coklat.',
            'declaration' => '1',
        ]);

    $response->assertRedirect(route('klaim.saya'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('claims', [
        'found_item_id' => $item->item_id,
        'claimant_id' => $claimant->user_id,
        'status' => 'pending',
        'proof_description' => 'Di dalam tas ada kartu mahasiswa atas nama Dede Firdaus dan dompet coklat.',
        'proof_image' => null,
        'lost_item_id' => null,
    ]);

    $claim = Claim::where('found_item_id', $item->item_id)->first();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $claimant->user_id,
        'entity_type' => 'claim',
        'entity_id' => $claim->claim_id,
        'action' => 'create',
    ]);
});

test('claimant can submit a claim with image proof and linked lost item', function () {
    Storage::fake('public');

    $claimant = User::factory()->create();
    $foundItem = Item::factory()->create(['type' => 'found', 'status' => 'open']);

    $lostItem = Item::factory()->create([
        'user_id' => $claimant->user_id,
        'type' => 'lost',
        'status' => 'open',
        'title' => 'Laporan Dompet Saya',
    ]);

    $file = fakeClaimPhoto('struk_pembelian.png');

    $response = $this->actingAs($claimant)
        ->post(route('klaim.store', $foundItem), [
            'lost_item_id' => $lostItem->item_id,
            'proof_description' => 'Terdapat struk pembelian dan nomor identitas yang sesuai.',
            'proof_image' => $file,
            'declaration' => '1',
        ]);

    $response->assertRedirect(route('klaim.saya'))
        ->assertSessionHas('success');

    $claim = Claim::where('found_item_id', $foundItem->item_id)->first();
    expect($claim)->not->toBeNull();
    expect($claim->lost_item_id)->toBe($lostItem->item_id);
    expect($claim->proof_image)->not->toBeNull();

    Storage::disk('public')->assertExists($claim->proof_image);
});

test('claim submission fails when proof_description is missing or too short', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['type' => 'found', 'status' => 'open']);

    $this->actingAs($user)
        ->post(route('klaim.store', $item), [
            'proof_description' => 'pendek',
            'declaration' => '1',
        ])
        ->assertSessionHasErrors('proof_description');

    $this->assertDatabaseEmpty('claims');
});

test('claim submission fails when declaration is missing', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['type' => 'found', 'status' => 'open']);

    $this->actingAs($user)
        ->post(route('klaim.store', $item), [
            'proof_description' => 'Ini barang saya yang hilang di koridor.',
        ])
        ->assertSessionHasErrors('declaration');

    $this->assertDatabaseEmpty('claims');
});

test('claim submission fails if linking a lost item belonging to someone else', function () {
    $claimant = User::factory()->create();
    $otherUser = User::factory()->create();
    $foundItem = Item::factory()->create(['type' => 'found', 'status' => 'open']);

    $otherLostItem = Item::factory()->create([
        'user_id' => $otherUser->user_id,
        'type' => 'lost',
        'status' => 'open',
    ]);

    $this->actingAs($claimant)
        ->post(route('klaim.store', $foundItem), [
            'lost_item_id' => $otherLostItem->item_id,
            'proof_description' => 'Deskripsi bukti kepemilikan yang valid.',
            'declaration' => '1',
        ])
        ->assertSessionHasErrors('lost_item_id');

    $this->assertDatabaseEmpty('claims');
});

test('card and modal CTA point to claim form when user is authenticated', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create([
        'title' => 'Payung Transparan',
        'type' => 'found',
        'status' => 'open',
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('klaim.create', $item));
});

test('claim form renders item detail modal without action buttons', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create([
        'type' => 'found',
        'status' => 'open',
        'title' => 'Tas Selempang Ijo',
    ]);

    $response = $this->actingAs($user)
        ->get(route('klaim.create', $item))
        ->assertOk();

    $response->assertDontSee('Ini Barang Anda?');
});
