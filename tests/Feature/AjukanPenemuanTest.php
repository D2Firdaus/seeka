<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemMatch;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fakeFindingPhoto(string $name): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

    return UploadedFile::fake()->createWithContent($name, $png);
}

test('guests are redirected to login when visiting the finding report form', function () {
    $item = Item::factory()->create(['type' => 'lost', 'status' => 'open']);

    $this->get(route('penemuan.create', $item))
        ->assertRedirect(route('login'));
});

test('users cannot submit finding for a found item', function () {
    $user = User::factory()->create();
    $foundItem = Item::factory()->create(['type' => 'found', 'status' => 'open']);

    $this->actingAs($user)
        ->get(route('penemuan.create', $foundItem))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('penemuan.store', $foundItem), [
            'finding_description' => 'Saya menemukan barang ini di perpustakaan.',
            'declaration' => '1',
        ])
        ->assertForbidden();
});

test('users cannot report finding for an item that is not open', function (string $status) {
    $user = User::factory()->create();
    $item = Item::factory()->create(['type' => 'lost', 'status' => $status]);

    $this->actingAs($user)
        ->get(route('penemuan.create', $item))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('penemuan.store', $item), [
            'finding_description' => 'Saya menemukan barang ini kemarin.',
            'declaration' => '1',
        ])
        ->assertForbidden();
})->with(['claimed', 'returned', 'closed']);

test('users cannot report finding for an item they reported lost themselves', function () {
    $reporter = User::factory()->create();
    $item = Item::factory()->create([
        'user_id' => $reporter->user_id,
        'type' => 'lost',
        'status' => 'open',
    ]);

    $this->actingAs($reporter)
        ->get(route('penemuan.create', $item))
        ->assertForbidden();

    $this->actingAs($reporter)
        ->post(route('penemuan.store', $item), [
            'finding_description' => 'Saya menemukan barang saya sendiri.',
            'declaration' => '1',
        ])
        ->assertForbidden();
});

test('users cannot submit multiple pending finding reports for the same lost item', function () {
    $user = User::factory()->create();
    $lostItem = Item::factory()->create(['type' => 'lost', 'status' => 'open']);

    $userFoundItem = Item::factory()->create([
        'user_id' => $user->user_id,
        'type' => 'found',
        'status' => 'open',
    ]);

    ItemMatch::factory()->create([
        'lost_item_id' => $lostItem->item_id,
        'found_item_id' => $userFoundItem->item_id,
        'status' => 'new',
    ]);

    $this->actingAs($user)
        ->get(route('penemuan.create', $lostItem))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('penemuan.store', $lostItem), [
            'finding_description' => 'Laporan penemuan kedua saya untuk barang ini.',
            'declaration' => '1',
        ])
        ->assertForbidden();
});

test('user can view the finding form for an open lost item', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Dokumen & Kartu']);
    $location = Location::factory()->create(['name' => 'Gedung Rektorat']);

    $item = Item::factory()->create([
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'type' => 'lost',
        'status' => 'open',
        'title' => 'KTM Mahasiswa Fasilkom',
    ]);

    $this->actingAs($user)
        ->get(route('penemuan.create', $item))
        ->assertOk()
        ->assertSee('Ajukan Penemuan')
        ->assertSee('KTM Mahasiswa Fasilkom')
        ->assertSee('Dokumen &amp; Kartu', false)
        ->assertSee('Gedung Rektorat');
});

test('finding form lists the users open found items for linking', function () {
    $user = User::factory()->create();
    $lostItem = Item::factory()->create(['type' => 'lost', 'status' => 'open']);

    $userFoundItem = Item::factory()->create([
        'user_id' => $user->user_id,
        'type' => 'found',
        'status' => 'open',
        'title' => 'KTM Temuan Saya di Parkiran',
    ]);

    $otherUserFoundItem = Item::factory()->create([
        'type' => 'found',
        'status' => 'open',
        'title' => 'KTM Temuan Orang Lain',
    ]);

    $this->actingAs($user)
        ->get(route('penemuan.create', $lostItem))
        ->assertOk()
        ->assertSee('KTM Temuan Saya di Parkiran')
        ->assertDontSee('KTM Temuan Orang Lain');
});

test('user can submit a valid finding without photo creating found item, match, and audit log', function () {
    $user = User::factory()->create();
    $lostItem = Item::factory()->create([
        'type' => 'lost',
        'status' => 'open',
        'title' => 'Flashdisk Sandisk 32GB',
    ]);

    $response = $this->actingAs($user)
        ->post(route('penemuan.store', $lostItem), [
            'finding_description' => 'Saya menemukan flashdisk merah ini tercolok di PC lab komputer 3.',
            'storage_location' => 'Pos Satpam Fasilkom',
            'declaration' => '1',
        ]);

    $response->assertRedirect(route('home'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('items', [
        'user_id' => $user->user_id,
        'type' => 'found',
        'status' => 'open',
        'description' => 'Saya menemukan flashdisk merah ini tercolok di PC lab komputer 3.',
        'storage_location' => 'Pos Satpam Fasilkom',
    ]);

    $foundItem = Item::where('user_id', $user->user_id)->where('type', 'found')->first();

    $this->assertDatabaseHas('item_matches', [
        'lost_item_id' => $lostItem->item_id,
        'found_item_id' => $foundItem->item_id,
        'pipeline_version' => 'manual',
        'status' => 'new',
    ]);

    $match = ItemMatch::where('lost_item_id', $lostItem->item_id)->first();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->user_id,
        'entity_type' => 'item_match',
        'entity_id' => $match->match_id,
        'action' => 'create',
    ]);
});

test('user can submit a finding with photo upload', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $lostItem = Item::factory()->create(['type' => 'lost', 'status' => 'open']);

    $file = fakeFindingPhoto('foto_kunci.png');

    $response = $this->actingAs($user)
        ->post(route('penemuan.store', $lostItem), [
            'finding_description' => 'Kunci motor ditemukan di koridor dekat tangga.',
            'storage_location' => 'Dititipkan ke petugas kebersihan',
            'proof_image' => $file,
            'declaration' => '1',
        ]);

    $response->assertRedirect(route('home'))
        ->assertSessionHas('success');

    $foundItem = Item::where('user_id', $user->user_id)->where('type', 'found')->first();
    expect($foundItem)->not->toBeNull();

    $this->assertDatabaseHas('item_images', [
        'item_id' => $foundItem->item_id,
    ]);

    $image = $foundItem->images()->first();
    expect($image)->not->toBeNull();
    Storage::disk('public')->assertExists($image->path);
});

test('user can submit a finding by linking an existing found item', function () {
    $user = User::factory()->create();
    $lostItem = Item::factory()->create(['type' => 'lost', 'status' => 'open']);

    $existingFound = Item::factory()->create([
        'user_id' => $user->user_id,
        'type' => 'found',
        'status' => 'open',
        'title' => 'Jaket Hitam Parka',
    ]);

    $response = $this->actingAs($user)
        ->post(route('penemuan.store', $lostItem), [
            'existing_found_item_id' => $existingFound->item_id,
            'finding_description' => 'Barang ini identik dengan jaket yang saya laporkan kemarin.',
            'declaration' => '1',
        ]);

    $response->assertRedirect(route('home'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('item_matches', [
        'lost_item_id' => $lostItem->item_id,
        'found_item_id' => $existingFound->item_id,
        'status' => 'new',
    ]);
});

test('finding submission fails when description is missing or too short', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['type' => 'lost', 'status' => 'open']);

    $this->actingAs($user)
        ->post(route('penemuan.store', $item), [
            'finding_description' => 'singkat',
            'declaration' => '1',
        ])
        ->assertSessionHasErrors('finding_description');

    $this->assertDatabaseEmpty('item_matches');
});

test('finding submission fails when declaration is missing', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['type' => 'lost', 'status' => 'open']);

    $this->actingAs($user)
        ->post(route('penemuan.store', $item), [
            'finding_description' => 'Saya menemukan barang ini di kantin kampus.',
        ])
        ->assertSessionHasErrors('declaration');

    $this->assertDatabaseEmpty('item_matches');
});

test('finding submission fails if linking a found item belonging to someone else', function () {
    $finder = User::factory()->create();
    $otherUser = User::factory()->create();
    $lostItem = Item::factory()->create(['type' => 'lost', 'status' => 'open']);

    $otherFoundItem = Item::factory()->create([
        'user_id' => $otherUser->user_id,
        'type' => 'found',
        'status' => 'open',
    ]);

    $this->actingAs($finder)
        ->post(route('penemuan.store', $lostItem), [
            'existing_found_item_id' => $otherFoundItem->item_id,
            'finding_description' => 'Saya menemukan barang ini di koridor lantai 2.',
            'declaration' => '1',
        ])
        ->assertSessionHasErrors('existing_found_item_id');

    $this->assertDatabaseEmpty('item_matches');
});

test('card and modal CTA point to penemuan form when user is authenticated for lost item', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create([
        'title' => 'Helm Bogo Hitam',
        'type' => 'lost',
        'status' => 'open',
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('penemuan.create', $item));
});

test('penemuan form renders item detail modal without action buttons', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create([
        'type' => 'lost',
        'status' => 'open',
        'title' => 'Kacamata Minus',
    ]);

    $response = $this->actingAs($user)
        ->get(route('penemuan.create', $item))
        ->assertOk();

    $response->assertDontSee('Menemukan Barang Ini?');
});
