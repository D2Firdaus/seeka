<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fakePhoto(string $name): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

    return UploadedFile::fake()->createWithContent($name, $png);
}

test('guest is redirected to login when opening the lost report form', function () {
    $this->get(route('lapor.kehilangan'))
        ->assertRedirect(route('login'));
});

test('guest cannot submit a lost report', function () {
    $this->post(route('lapor.kehilangan'), [])
        ->assertRedirect(route('login'));

    $this->assertDatabaseCount('items', 0);
});

test('lost report form renders the report fields', function () {
    Category::factory()->create(['name' => 'Elektronik']);
    Location::factory()->create(['name' => 'Perpustakaan']);

    $this->actingAs(User::factory()->create())
        ->get(route('lapor.kehilangan'))
        ->assertOk()
        ->assertSee('Buat Laporan Kehilangan')
        ->assertSee('Judul Barang')
        ->assertSee('Elektronik')
        ->assertSee('Perpustakaan')
        ->assertSee('Kirim Laporan');
});

test('submitting the form creates an open lost report', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $location = Location::factory()->create();

    $response = $this->actingAs($user)->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
    ]);

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('success', 'Laporan kehilangan berhasil dibuat.');

    $this->assertDatabaseHas('items', [
        'user_id' => $user->user_id,
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'type' => 'lost',
        'title' => 'Dompet hitam',
        'status' => 'open',
        'hidden_detail' => null,
        'storage_location' => null,
    ]);
});

test('successful report shows the success message on the home page', function () {
    $category = Category::factory()->create();
    $location = Location::factory()->create();

    $this->actingAs(User::factory()->create())->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Laporan kehilangan berhasil dibuat.');
});

test('photos are stored with the report', function () {
    Storage::fake('public');

    $category = Category::factory()->create();
    $location = Location::factory()->create();

    $this->actingAs(User::factory()->create())->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
        'photos' => [fakePhoto('dompet.png'), fakePhoto('isi.png')],
    ]);

    $item = Item::sole();

    expect($item->images)->toHaveCount(2);

    $this->assertDatabaseCount('item_images', 2);

    foreach ($item->images as $image) {
        Storage::disk('public')->assertExists($image->path);
    }
});

test('submitting an empty form shows validation errors', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('lapor.kehilangan'), []);

    $response->assertSessionHasErrors([
        'title',
        'category_id',
        'location_id',
        'date_event',
        'description',
    ]);

    $this->assertDatabaseCount('items', 0);
});

test('a future event date is rejected', function () {
    $category = Category::factory()->create();
    $location = Location::factory()->create();

    $response = $this->actingAs(User::factory()->create())->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'date_event' => now()->addDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
    ]);

    $response->assertSessionHasErrors([
        'date_event' => 'Tanggal kejadian tidak boleh di masa depan.',
    ]);

    $this->assertDatabaseCount('items', 0);
});

test('an unknown category is rejected', function () {
    $location = Location::factory()->create();

    $response = $this->actingAs(User::factory()->create())->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => 999999,
        'location_id' => $location->location_id,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
    ]);

    $response->assertSessionHasErrors([
        'category_id' => 'Kategori tidak valid.',
    ]);
});

test('an unknown location is rejected', function () {
    $category = Category::factory()->create();

    $response = $this->actingAs(User::factory()->create())->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => $category->category_id,
        'location_id' => 999999,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
    ]);

    $response->assertSessionHasErrors([
        'location_id' => 'Lokasi kejadian tidak valid.',
    ]);
});

test('a non-image photo is rejected', function () {
    $category = Category::factory()->create();
    $location = Location::factory()->create();

    $response = $this->actingAs(User::factory()->create())->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
        'photos' => [UploadedFile::fake()->createWithContent('dokumen.pdf', 'bukan gambar')],
    ]);

    $response->assertSessionHasErrors([
        'photos.0' => 'File harus berupa gambar.',
    ]);

    $this->assertDatabaseCount('items', 0);
});

test('hidden detail and storage location cannot be submitted through the form', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $location = Location::factory()->create();

    $this->actingAs($user)->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
        'hidden_detail' => 'Ciri khusus yang tidak boleh tersimpan',
        'storage_location' => 'Gudang petugas',
    ]);

    $this->assertDatabaseHas('items', [
        'user_id' => $user->user_id,
        'hidden_detail' => null,
        'storage_location' => null,
    ]);
});
