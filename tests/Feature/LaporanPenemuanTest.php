<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fakeFoundPhoto(string $name): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

    return UploadedFile::fake()->createWithContent($name, $png);
}

function foundReportPayload(array $overrides = []): array
{
    $category = Category::factory()->create();
    $location = Location::factory()->create();

    return array_merge([
        'title' => 'Kunci motor Honda',
        'category_id' => $category->category_id,
        'location_id' => $location->location_id,
        'date_event' => now()->subDay()->toDateString(),
        'description' => 'Kunci dengan gantungan kunci biru.',
        'hidden_detail' => 'Ada stiker bintang di balik gantungan.',
        'storage_location' => 'Meja petugas keamanan.',
        'declaration' => '1',
        'photos' => [fakeFoundPhoto('kunci.png')],
    ], $overrides);
}

test('guest is redirected to login when opening the found report form', function () {
    $this->get(route('lapor.penemuan'))
        ->assertRedirect(route('login'));
});

test('guest cannot submit a found report', function () {
    $this->post(route('lapor.penemuan'), [])
        ->assertRedirect(route('login'));

    $this->assertDatabaseCount('items', 0);
});

test('found report form renders the report fields', function () {
    Category::factory()->create(['name' => 'Elektronik']);
    Location::factory()->create(['name' => 'Perpustakaan']);

    $this->actingAs(User::factory()->create())
        ->get(route('lapor.penemuan'))
        ->assertOk()
        ->assertSee('Laporan Penemuan')
        ->assertSee('Menemukan barang?')
        ->assertSee('Nama Barang')
        ->assertSee('Kategori')
        ->assertSee('Deskripsi Barang')
        ->assertSee('Lokasi Penemuan')
        ->assertSee('Tanggal Penemuan')
        ->assertSee('Ciri Khusus Barang')
        ->assertSee('Penyimpanan Saat Ini')
        ->assertSee('Elektronik')
        ->assertSee('Perpustakaan')
        ->assertSee('Pilih Foto')
        ->assertSee('JPG atau PNG Maks. 5 MB per foto hingga 3 foto')
        ->assertSee('Saya Menyatakan Informasi Dalam Laporan Ini Benar Adanya')
        ->assertSee('Batal');
});

test('submitting the form creates an open found report with the private details', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('lapor.penemuan'), foundReportPayload());

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('success', 'Laporan penemuan berhasil dibuat.');

    $this->assertDatabaseHas('items', [
        'user_id' => $user->user_id,
        'type' => 'found',
        'title' => 'Kunci motor Honda',
        'status' => 'open',
        'hidden_detail' => 'Ada stiker bintang di balik gantungan.',
        'storage_location' => 'Meja petugas keamanan.',
    ]);
});

test('photos are stored with the found report', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->create())->post(route('lapor.penemuan'), foundReportPayload([
        'photos' => [fakeFoundPhoto('a.png'), fakeFoundPhoto('b.png')],
    ]));

    $item = Item::sole();

    expect($item->images)->toHaveCount(2);

    $this->assertDatabaseCount('item_images', 2);

    foreach ($item->images as $image) {
        Storage::disk('public')->assertExists($image->path);
    }
});

test('three photos are stored with the found report', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->create())->post(route('lapor.penemuan'), foundReportPayload([
        'photos' => [fakeFoundPhoto('a.png'), fakeFoundPhoto('b.png'), fakeFoundPhoto('c.png')],
    ]));

    $this->assertDatabaseCount('item_images', 3);
});

test('at least one photo is required', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('lapor.penemuan'), foundReportPayload(['photos' => []]));

    $response->assertSessionHasErrors([
        'photos' => 'Foto barang wajib diunggah.',
    ]);

    $this->assertDatabaseCount('items', 0);
});

test('more than three photos are rejected', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('lapor.penemuan'), foundReportPayload([
            'photos' => [fakeFoundPhoto('a.png'), fakeFoundPhoto('b.png'), fakeFoundPhoto('c.png'), fakeFoundPhoto('d.png')],
        ]));

    $response->assertSessionHasErrors([
        'photos' => 'Maksimal 3 foto.',
    ]);

    $this->assertDatabaseCount('items', 0);
});

test('a non-image photo is rejected', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('lapor.penemuan'), foundReportPayload([
            'photos' => [UploadedFile::fake()->createWithContent('dokumen.pdf', 'bukan gambar')],
        ]));

    $response->assertSessionHasErrors([
        'photos.0' => 'File harus berupa gambar.',
    ]);

    $this->assertDatabaseCount('items', 0);
});

test('a future event date is rejected', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('lapor.penemuan'), foundReportPayload([
            'date_event' => now()->addDay()->toDateString(),
        ]));

    $response->assertSessionHasErrors([
        'date_event' => 'Tanggal penemuan tidak boleh di masa depan.',
    ]);

    $this->assertDatabaseCount('items', 0);
});

test('an unknown category is rejected', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('lapor.penemuan'), foundReportPayload([
            'category_id' => 999999,
        ]));

    $response->assertSessionHasErrors([
        'category_id' => 'Kategori tidak valid.',
    ]);
});

test('an unknown location is rejected', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('lapor.penemuan'), foundReportPayload([
            'location_id' => 999999,
        ]));

    $response->assertSessionHasErrors([
        'location_id' => 'Lokasi penemuan tidak valid.',
    ]);
});

test('submitting an empty form shows validation errors', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('lapor.penemuan'), []);

    $response->assertSessionHasErrors([
        'title',
        'category_id',
        'location_id',
        'date_event',
        'description',
        'hidden_detail',
        'declaration',
        'photos',
    ]);

    $this->assertDatabaseCount('items', 0);
});

test('storage location is optional', function () {
    Storage::fake('public');

    $response = $this->actingAs(User::factory()->create())
        ->post(route('lapor.penemuan'), foundReportPayload(['storage_location' => '']));

    $response->assertRedirect(route('home'));

    $this->assertDatabaseHas('items', [
        'type' => 'found',
        'storage_location' => null,
    ]);
});

test('successful found report shows the success message on the home page', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->create())
        ->post(route('lapor.penemuan'), foundReportPayload());

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Laporan penemuan berhasil dibuat.');
});

test('hidden detail and storage location are not shown on the beranda', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->create())->post(route('lapor.penemuan'), foundReportPayload([
        'hidden_detail' => 'RAHASIA-STIKER-BINTANG',
        'storage_location' => 'RAHASIA-MEJA-PETUGAS',
    ]));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Kunci motor Honda')
        ->assertDontSee('RAHASIA-STIKER-BINTANG')
        ->assertDontSee('RAHASIA-MEJA-PETUGAS');
});
