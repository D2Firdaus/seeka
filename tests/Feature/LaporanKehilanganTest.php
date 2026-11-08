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
        ->assertSee('Laporan Kehilangan')
        ->assertSee('Kehilangan?, coba ceritakan detailnya agar lebih mudah dikenali')
        ->assertSee('Nama Barang')
        ->assertSee('Kategori')
        ->assertSee('Deskripsi Barang')
        ->assertSee('Lokasi Kehilangan')
        ->assertSee('Tanggal Kehilangan')
        ->assertSee('Elektronik')
        ->assertSee('Perpustakaan')
        ->assertSee('Pilih Foto')
        ->assertSee('JPG atau PNG Maks. 5 MB per foto hingga 3 foto')
        ->assertSee('Saya Menyatakan Informasi Dalam Laporan Ini Benar Adanya')
        ->assertSee('Batal');
});

test('submitting the form creates an open lost report', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $category = Category::factory()->create();
    $location = Location::factory()->create();

    $response = $this->actingAs($user)->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
        'declaration' => '1',
        'photos' => [fakePhoto('dompet.png')],
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
    Storage::fake('public');

    $category = Category::factory()->create();
    $location = Location::factory()->create();

    $this->actingAs(User::factory()->create())->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
        'declaration' => '1',
        'photos' => [fakePhoto('dompet.png')],
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
        'declaration' => '1',
        'photos' => [fakePhoto('dompet.png'), fakePhoto('isi.png')],
    ]);

    $item = Item::sole();

    expect($item->images)->toHaveCount(2);

    $this->assertDatabaseCount('item_images', 2);

    foreach ($item->images as $image) {
        Storage::disk('public')->assertExists($image->path);
    }
});

test('three photos are stored with the report', function () {
    Storage::fake('public');

    $category = Category::factory()->create();
    $location = Location::factory()->create();

    $this->actingAs(User::factory()->create())->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
        'declaration' => '1',
        'photos' => [fakePhoto('a.png'), fakePhoto('b.png'), fakePhoto('c.png')],
    ]);

    $this->assertDatabaseCount('item_images', 3);
});

test('a report requires at least one photo', function () {
    $category = Category::factory()->create();
    $location = Location::factory()->create();

    $response = $this->actingAs(User::factory()->create())->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
        'declaration' => '1',
    ]);

    $response->assertSessionHasErrors([
        'photos' => 'Foto barang wajib diunggah.',
    ]);

    $this->assertDatabaseCount('items', 0);
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
        'declaration',
        'photos',
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
        'declaration' => '1',
    ]);

    $response->assertSessionHasErrors([
        'date_event' => 'Tanggal kehilangan tidak boleh di masa depan.',
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
        'declaration' => '1',
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
        'declaration' => '1',
    ]);

    $response->assertSessionHasErrors([
        'location_id' => 'Lokasi kehilangan tidak valid.',
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
        'declaration' => '1',
        'photos' => [UploadedFile::fake()->createWithContent('dokumen.pdf', 'bukan gambar')],
    ]);

    $response->assertSessionHasErrors([
        'photos.0' => 'File harus berupa gambar.',
    ]);

    $this->assertDatabaseCount('items', 0);
});

test('more than three photos are rejected', function () {
    $category = Category::factory()->create();
    $location = Location::factory()->create();

    $response = $this->actingAs(User::factory()->create())->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
        'declaration' => '1',
        'photos' => [fakePhoto('a.png'), fakePhoto('b.png'), fakePhoto('c.png'), fakePhoto('d.png')],
    ]);

    $response->assertSessionHasErrors([
        'photos' => 'Maksimal 3 foto.',
    ]);

    $this->assertDatabaseCount('items', 0);
});

test('hidden detail and storage location cannot be submitted through the form', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $category = Category::factory()->create();
    $location = Location::factory()->create();

    $this->actingAs($user)->post(route('lapor.kehilangan'), [
        'title' => 'Dompet hitam',
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Dompet hitam dengan struk parkir di dalamnya.',
        'declaration' => '1',
        'photos' => [fakePhoto('dompet.png')],
        'hidden_detail' => 'Ciri khusus yang tidak boleh tersimpan',
        'storage_location' => 'Gudang petugas',
    ]);

    $this->assertDatabaseHas('items', [
        'user_id' => $user->user_id,
        'hidden_detail' => null,
        'storage_location' => null,
    ]);
});
