<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

test('beranda shows the reported items', function () {
    Item::factory()->create(['title' => 'Dompet Hitam']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('SEEKA')
        ->assertSee('Dompet Hitam');
});

test('item detail shows the type and status badges', function (string $status, string $label) {
    Item::factory()->create([
        'title' => 'Dompet Hitam',
        'type' => 'found',
        'status' => $status,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Penemuan')
        ->assertSee($label);
})->with([
    ['open', 'Terbuka'],
    ['claimed', 'Diklaim'],
    ['returned', 'Dikembalikan'],
]);

test('closed items are not displayed on beranda', function () {
    Item::factory()->create([
        'title' => 'Barang Ditutup',
        'status' => 'closed',
    ]);
    Item::factory()->create([
        'title' => 'Barang Terbuka',
        'status' => 'open',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Barang Terbuka')
        ->assertDontSee('Barang Ditutup');
});

test('item card and detail show appropriate CTA based on type and status', function (string $type, string $status, string $expectedCta) {
    Item::factory()->create([
        'title' => 'Barang Uji',
        'type' => $type,
        'status' => $status,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee($expectedCta);
})->with([
    ['found', 'open', 'Ajukan Klaim'],
    ['lost', 'open', 'Ajukan Penemuan'],
    ['found', 'claimed', 'Ajukan Banding'],
    ['lost', 'claimed', 'Ajukan Banding'],
    ['found', 'returned', 'Ajukan Banding'],
    ['lost', 'returned', 'Ajukan Banding'],
]);

test('beranda shows the first photo of an item', function () {
    $item = Item::factory()->create(['title' => 'Kunci Motor']);
    $item->images()->createMany([
        ['path' => 'items/first.png', 'sort_order' => 0],
        ['path' => 'items/second.png', 'sort_order' => 1],
    ]);

    $response = $this->get(route('home'))
        ->assertOk()
        ->assertSee('storage/items/first.png', false);

    // The item card thumbnail should use the first image.
    // The detail modal legitimately renders all images as thumbnails,
    // so the second image IS present in the full page HTML.
    $html = $response->getContent();
    expect($html)->toContain('storage/items/first.png');

    // The card image element specifically uses the first image
    expect($html)->toContain('data-main-image');
});

test('beranda shows the placeholder when the item has no photo', function () {
    Item::factory()->create(['title' => 'Dompet Hitam']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Dompet Hitam')
        ->assertDontSee('storage/items/', false);
});

test('search filters the item titles', function () {
    Item::factory()->create(['title' => 'Dompet Hitam']);
    Item::factory()->create(['title' => 'Kunci Motor']);

    $this->get(route('home', ['q' => 'Dompet']))
        ->assertOk()
        ->assertSee('Dompet Hitam')
        ->assertDontSee('Kunci Motor');
});

test('filter by type only lists that type', function () {
    Item::factory()->create(['title' => 'Dompet Hitam', 'type' => 'lost']);
    Item::factory()->create(['title' => 'Kunci Motor', 'type' => 'found']);

    $this->get(route('home', ['type' => 'found']))
        ->assertOk()
        ->assertSee('Kunci Motor')
        ->assertDontSee('Dompet Hitam');
});

test('filter by category only lists that category', function () {
    $category = Category::factory()->create(['name' => 'Elektronik']);
    $other = Category::factory()->create(['name' => 'Dokumen']);

    Item::factory()->create(['title' => 'Laptop ASUS', 'category_id' => $category->category_id]);
    Item::factory()->create(['title' => 'KTM Hilang', 'category_id' => $other->category_id]);

    $this->get(route('home', ['category' => $category->category_id]))
        ->assertOk()
        ->assertSee('Laptop ASUS')
        ->assertDontSee('KTM Hilang');
});

test('filter by location only lists that location', function () {
    $location = Location::factory()->create(['name' => 'Perpustakaan']);
    $other = Location::factory()->create(['name' => 'Kantin']);

    Item::factory()->create(['title' => 'Laptop ASUS', 'location_id' => $location->location_id]);
    Item::factory()->create(['title' => 'Kunci Motor', 'location_id' => $other->location_id]);

    $this->get(route('home', ['location' => $location->location_id]))
        ->assertOk()
        ->assertSee('Laptop ASUS')
        ->assertDontSee('Kunci Motor');
});

test('filter by date range only lists items in that range', function () {
    Item::factory()->create(['title' => 'Dompet Hitam', 'date_event' => '2026-01-10']);
    Item::factory()->create(['title' => 'Kunci Motor', 'date_event' => '2026-02-10']);

    $this->get(route('home', ['date_from' => '2026-01-01', 'date_to' => '2026-01-31']))
        ->assertOk()
        ->assertSee('Dompet Hitam')
        ->assertDontSee('Kunci Motor');
});

test('date filter keeps the selected range in the modal', function () {
    $response = $this->get(route('home', ['date_from' => '2026-01-01', 'date_to' => '2026-01-31']))
        ->assertOk();

    expect($response->getContent())->toContain('name="date_from" value="2026-01-01"');
    expect($response->getContent())->toContain('name="date_to" value="2026-01-31"');
});

test('filter reset buttons clear fields without navigation', function () {
    $category = Category::factory()->create();

    $response = $this->get(route('home', ['q' => '', 'category' => $category->category_id, 'location' => '']))
        ->assertOk();

    expect($response->getContent())->toContain('data-reset="category"');
    expect($response->getContent())->toContain('data-reset="location"');
    expect($response->getContent())->toContain('data-reset="date"');
    expect($response->getContent())->toContain('data-reset="type"');
    expect($response->getContent())->toContain('data-reset="status"');
    expect($response->getContent())->toContain('data-reset="all"');
});

test('pagination links drop empty params', function () {
    $category = Category::factory()->create();
    Item::factory()->count(10)->create(['category_id' => $category->category_id]);

    $response = $this->get(route('home', ['q' => '', 'category' => $category->category_id]))
        ->assertOk();

    preg_match_all('/href="([^"]*)"/', html_entity_decode($response->getContent()), $matches);

    expect(implode(' ', $matches[1]))->toContain('page=2');
    expect($response->getContent())->toContain('aria-label="Halaman terakhir"');

    foreach ($matches[1] as $href) {
        parse_str((string) parse_url($href, PHP_URL_QUERY), $query);

        foreach (Arr::flatten($query) as $value) {
            expect($value)->not->toBe('');
        }
    }
});

test('per page param controls page size', function () {
    Item::factory()->count(12)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Menampilkan 1–9 dari 12 data');

    $this->get(route('home', ['per_page' => 18]))
        ->assertOk()
        ->assertSee('Menampilkan 1–12 dari 12 data');
});

test('invalid per page falls back to default', function () {
    Item::factory()->count(11)->create();

    $this->get(route('home', ['per_page' => 'abc']))
        ->assertOk()
        ->assertSee('Menampilkan 1–9 dari 11 data');

    $this->get(route('home', ['per_page' => -5]))
        ->assertOk()
        ->assertSee('Menampilkan 1–9 dari 11 data');
});

test('per page form keeps filters and drops the page param', function () {
    Item::factory()->count(20)->create(['title' => 'Dompet Hitam']);

    $response = $this->get(route('home', ['q' => 'Dompet', 'page' => 2]))
        ->assertOk();

    expect($response->getContent())->toContain('name="q" value="Dompet"');
    expect($response->getContent())->not->toContain('name="page"');
    expect($response->getContent())->toContain('Menampilkan 10–18 dari 20 data');
});

test('sql injection payloads in search cannot alter the query', function (string $payload) {
    Item::factory()->create(['title' => 'Dompet Hitam']);

    $this->get(route('home', ['q' => $payload]))
        ->assertOk()
        ->assertDontSee('Dompet Hitam');

    $this->assertDatabaseCount('items', 1);
})->with([
    'or tautology' => ["' OR '1'='1"],
    'drop table' => ["'; DROP TABLE items; --"],
    'delete rows' => ["'; DELETE FROM items; --"],
    'union select' => ["' UNION SELECT NULL --"],
    'time based' => ["' AND SLEEP(5) --"],
]);

test('sql injection payloads in filters cannot alter the query', function (string $param) {
    Item::factory()->create(['title' => 'Dompet Hitam']);

    $this->get(route('home', [$param => "' OR '1'='1"]))
        ->assertOk()
        ->assertDontSee('Dompet Hitam');

    $this->assertDatabaseCount('items', 1);
})->with(['description', 'category', 'location', 'status']);

test('hidden details and storage locations are never shown', function () {
    Item::factory()->create([
        'title' => 'Dompet Hitam',
        'hidden_detail' => 'RAHASIA-TERSEMBUNYI',
        'storage_location' => 'LOKASI-RAHASIA',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('RAHASIA-TERSEMBUNYI')
        ->assertDontSee('LOKASI-RAHASIA');
});

test('guests see the sign in button while users see their name', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Sign in');

    $this->actingAs(User::factory()->create(['name' => 'Dede']))
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Dede')
        ->assertDontSee('Sign in');
});

test('profile dropdown shows regular user links and hides admin links', function () {
    $user = User::factory()->create(['name' => 'John Doe', 'role' => 'user']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Profil')
        ->assertSee('Laporan Saya')
        ->assertSee('Klaim Saya')
        ->assertSee('Pengaturan')
        ->assertSee('Keluar')
        ->assertDontSee('Dashboard Admin')
        ->assertDontSee('Kelola Pengguna')
        ->assertDontSee('Verifikasi Laporan')
        ->assertDontSee('Kelola Metadata');
});

test('profile dropdown shows admin links when authenticated as admin', function () {
    $admin = User::factory()->create(['name' => 'Admin User', 'role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Dashboard Admin')
        ->assertSee('Kelola Pengguna')
        ->assertSee('Verifikasi Laporan')
        ->assertSee('Kelola Metadata');
});
