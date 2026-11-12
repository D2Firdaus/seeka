<?php

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function laporanSayaPhoto(string $name): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

    return UploadedFile::fake()->createWithContent($name, $png);
}

test('guest is redirected to login from the my reports pages', function () {
    $item = Item::factory()->create();

    $this->get(route('laporan.saya'))->assertRedirect(route('login'));
    $this->get(route('laporan.edit', $item))->assertRedirect(route('login'));
    $this->put(route('laporan.update', $item))->assertRedirect(route('login'));
    $this->patch(route('laporan.close', $item))->assertRedirect(route('login'));
    $this->delete(route('laporan.destroy', $item))->assertRedirect(route('login'));
});

test('the index page shows the page heading without a sub-navbar', function () {
    $user = User::factory()->create();
    Item::factory()->create(['user_id' => $user->user_id, 'status' => 'open']);
    Item::factory()->create(['user_id' => $user->user_id, 'status' => 'claimed']);

    $this->actingAs($user)
        ->get(route('laporan.saya'))
        ->assertOk()
        ->assertSee('<h1 class="text-display font-display">Laporan Saya</h1>', false)
        ->assertDontSee('Semua Laporan')
        ->assertDontSee('Pencarian');
});

test('the index page only shows the authenticated user reports', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Item::factory()->create(['user_id' => $user->user_id, 'title' => 'Laptop Milik Saya']);
    Item::factory()->create(['user_id' => $other->user_id, 'title' => 'Laptop Milik Orang Lain']);

    $this->actingAs($user)
        ->get(route('laporan.saya'))
        ->assertOk()
        ->assertSee('Laptop Milik Saya')
        ->assertDontSee('Laptop Milik Orang Lain');
});

test('the index page includes closed reports and does not show edit or delete actions', function () {
    $user = User::factory()->create();
    Item::factory()->create(['user_id' => $user->user_id, 'title' => 'Dompet Tertutup', 'status' => 'closed']);

    $this->actingAs($user)
        ->get(route('laporan.saya'))
        ->assertOk()
        ->assertSee('Dompet Tertutup')
        ->assertSee('Ditutup')
        ->assertDontSee('title="Tutup Laporan"', false)
        ->assertDontSee('aria-label="Edit Laporan"', false)
        ->assertDontSee('aria-label="Hapus Laporan"', false);
});

test('open reports show direct edit, close and delete action buttons', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['user_id' => $user->user_id, 'title' => 'Kunci Motor', 'status' => 'open']);

    $this->actingAs($user)
        ->get(route('laporan.saya'))
        ->assertOk()
        ->assertSee('Kunci Motor')
        ->assertSee(route('laporan.edit', $item))
        ->assertSee(route('laporan.close', $item))
        ->assertSee(route('laporan.destroy', $item))
        ->assertSee('title="Tutup Laporan"', false)
        ->assertSee('aria-label="Edit Laporan"', false)
        ->assertSee('aria-label="Hapus Laporan"', false);
});

test('the owner can close an open report', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['user_id' => $user->user_id, 'title' => 'Dompet Hitam', 'status' => 'open']);

    $this->actingAs($user)
        ->patch(route('laporan.close', $item))
        ->assertRedirect(route('laporan.saya'))
        ->assertSessionHas('success', 'Laporan berhasil ditutup.');

    $this->assertDatabaseHas('items', [
        'item_id' => $item->item_id,
        'status' => 'closed',
        'deleted_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('laporan.saya'))
        ->assertOk()
        ->assertSee('Dompet Hitam')
        ->assertSee('Ditutup')
        ->assertDontSee('title="Tutup Laporan"', false);
});

test('the index page filters by type, status and title', function () {
    $user = User::factory()->create();

    Item::factory()->create(['user_id' => $user->user_id, 'type' => 'lost', 'title' => 'Dompet Hitam', 'status' => 'open']);
    Item::factory()->create(['user_id' => $user->user_id, 'type' => 'found', 'title' => 'Kunci Motor', 'status' => 'open']);

    $this->actingAs($user)
        ->get(route('laporan.saya', ['type' => 'lost']))
        ->assertOk()
        ->assertSee('Dompet Hitam')
        ->assertDontSee('Kunci Motor');

    $this->actingAs($user)
        ->get(route('laporan.saya', ['q' => 'Kunci']))
        ->assertOk()
        ->assertSee('Kunci Motor')
        ->assertDontSee('Dompet Hitam');

    $this->actingAs($user)
        ->get(route('laporan.saya', ['status' => 'closed']))
        ->assertOk()
        ->assertDontSee('Dompet Hitam')
        ->assertDontSee('Kunci Motor');
});

test('the index page shows an empty state without reports and no add buttons', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('laporan.saya'))
        ->assertOk()
        ->assertSee('Anda belum membuat laporan apa pun.')
        ->assertDontSee('Buat Laporan Kehilangan')
        ->assertDontSee('Buat Laporan Penemuan')
        ->assertDontSee('+ Lapor Kehilangan')
        ->assertDontSee('+ Lapor Penemuan');
});

test('the owner sees a prefilled edit form for an open report', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create([
        'user_id' => $user->user_id,
        'type' => 'lost',
        'title' => 'Dompet Hitam',
        'description' => 'Dompet kulit warna hitam',
        'status' => 'open',
    ]);

    $this->actingAs($user)
        ->get(route('laporan.edit', $item))
        ->assertOk()
        ->assertSee('Edit Laporan Kehilangan')
        ->assertSee('Dompet Hitam', false)
        ->assertSee('Dompet kulit warna hitam', false);
});

test('the edit page offers removing existing photos with an x button instead of a checkbox', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['user_id' => $user->user_id, 'status' => 'open']);
    $image = $item->images()->create(['path' => 'items/existing.png', 'sort_order' => 0]);

    $html = $this->actingAs($user)
        ->get(route('laporan.edit', $item))
        ->assertOk()
        ->assertSee('Foto saat ini — klik X untuk menghapus.')
        ->assertSee('data-existing-photo-remove="'.$image->image_id.'"', false)
        ->assertSee('aria-label="Hapus foto"', false)
        ->getContent();

    expect($html)
        ->not->toContain('type="checkbox" name="remove_images[]"');
});

test('the edit page shows the report card in an aside on the right', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create([
        'user_id' => $user->user_id,
        'type' => 'lost',
        'title' => 'Dompet Hitam',
        'status' => 'open',
    ]);

    $html = $this->actingAs($user)
        ->get(route('laporan.edit', $item))
        ->assertOk()
        ->assertSee('Detail Laporan')
        ->getContent();

    $aside = Str::between($html, '<aside', '</aside>');

    expect($aside)
        ->toContain('Detail Laporan')
        ->toContain('Dompet Hitam');
});

test('the edit form shows found report specific fields', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create([
        'user_id' => $user->user_id,
        'type' => 'found',
        'title' => 'Kunci Motor',
        'status' => 'open',
    ]);

    $this->actingAs($user)
        ->get(route('laporan.edit', $item))
        ->assertOk()
        ->assertSee('Edit Laporan Penemuan')
        ->assertSee('Ciri Khusus Barang')
        ->assertSee('Penyimpanan Saat Ini');
});

test('another user cannot view edit form or modify a report', function () {
    $item = Item::factory()->create(['status' => 'open']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('laporan.edit', $item))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->put(route('laporan.update', $item), ['title' => 'Hacked'])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('laporan.destroy', $item))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->patch(route('laporan.close', $item))
        ->assertForbidden();

    $this->assertDatabaseHas('items', ['item_id' => $item->item_id, 'title' => $item->title, 'deleted_at' => null]);
});

test('a report that is not open cannot be edited or deleted', function () {
    $user = User::factory()->create();

    foreach (['claimed', 'returned', 'closed'] as $status) {
        $item = Item::factory()->create(['user_id' => $user->user_id, 'status' => $status]);

        $this->actingAs($user)
            ->get(route('laporan.edit', $item))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('laporan.update', $item), ['title' => 'Baru'])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('laporan.destroy', $item))
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('laporan.close', $item))
            ->assertForbidden();

        $this->assertDatabaseHas('items', ['item_id' => $item->item_id, 'title' => $item->title, 'deleted_at' => null]);
    }
});

test('the owner can update an open report', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create([
        'user_id' => $user->user_id,
        'type' => 'lost',
        'title' => 'Dompet Hitam',
        'status' => 'open',
    ]);

    $this->actingAs($user)
        ->put(route('laporan.update', $item), [
            'title' => 'Dompet Kulit Hitam',
            'category_id' => $item->category_id,
            'location_id' => $item->location_id,
            'date_event' => now()->toDateString(),
            'description' => 'Dompet kulit warna hitam dengan kartu di dalamnya',
        ])
        ->assertRedirect(route('laporan.saya'))
        ->assertSessionHas('success', 'Laporan berhasil diperbarui.');

    $this->assertDatabaseHas('items', [
        'item_id' => $item->item_id,
        'title' => 'Dompet Kulit Hitam',
        'deleted_at' => null,
    ]);
});

test('updating a report validates the required fields', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['user_id' => $user->user_id, 'status' => 'open']);

    $this->actingAs($user)
        ->from(route('laporan.edit', $item))
        ->put(route('laporan.update', $item), ['title' => ''])
        ->assertRedirect(route('laporan.edit', $item))
        ->assertSessionHasErrors('title');

    $this->assertDatabaseHas('items', ['item_id' => $item->item_id, 'title' => $item->title]);
});

test('a found report requires the hidden detail on update', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create([
        'user_id' => $user->user_id,
        'type' => 'found',
        'status' => 'open',
    ]);

    $this->actingAs($user)
        ->put(route('laporan.update', $item), [
            'title' => $item->title,
            'category_id' => $item->category_id,
            'location_id' => $item->location_id,
            'date_event' => now()->toDateString(),
            'description' => $item->description,
        ])
        ->assertSessionHasErrors('hidden_detail');
});

test('the owner can replace photos on an open report', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $item = Item::factory()->create(['user_id' => $user->user_id, 'type' => 'lost', 'status' => 'open']);
    $oldImage = $item->images()->create(['path' => 'items/old.png', 'sort_order' => 0]);

    $this->actingAs($user)
        ->put(route('laporan.update', $item), [
            'title' => $item->title,
            'category_id' => $item->category_id,
            'location_id' => $item->location_id,
            'date_event' => now()->toDateString(),
            'description' => $item->description,
            'remove_images' => [$oldImage->image_id],
            'photos' => [laporanSayaPhoto('baru.png')],
        ])
        ->assertRedirect(route('laporan.saya'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('item_images', ['image_id' => $oldImage->image_id]);
    $this->assertCount(1, $item->images()->get());
});

test('the photo total cannot exceed three on update', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $item = Item::factory()->create(['user_id' => $user->user_id, 'type' => 'lost', 'status' => 'open']);

    foreach (range(1, 3) as $index) {
        $item->images()->create(['path' => "items/{$index}.png", 'sort_order' => $index]);
    }

    $this->actingAs($user)
        ->put(route('laporan.update', $item), [
            'title' => $item->title,
            'category_id' => $item->category_id,
            'location_id' => $item->location_id,
            'date_event' => now()->toDateString(),
            'description' => $item->description,
            'photos' => [laporanSayaPhoto('keempat.png')],
        ])
        ->assertSessionHasErrors('photos');

    $this->assertCount(3, $item->images()->get());
});

test('the owner can delete an open report', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['user_id' => $user->user_id, 'status' => 'open', 'title' => 'Dompet Hitam']);

    $this->actingAs($user)
        ->delete(route('laporan.destroy', $item))
        ->assertRedirect(route('laporan.saya'))
        ->assertSessionHas('success', 'Laporan berhasil dihapus.');

    $this->assertSoftDeleted('items', ['item_id' => $item->item_id]);

    $this->actingAs($user)
        ->get(route('laporan.saya'))
        ->assertOk()
        ->assertDontSee('Dompet Hitam');
});

test('the navbar links to the my reports page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('laporan.saya'), false);
});

test('the navbar on my reports page has no sub-navbar tabs', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)
        ->get(route('laporan.saya'))
        ->assertOk()
        ->getContent();

    $navbar = Str::between($html, '<header', '</header>');

    expect($navbar)
        ->not->toContain('Semua Laporan')
        ->not->toContain('Pencarian')
        ->not->toContain('Kategori Laporan');
});
