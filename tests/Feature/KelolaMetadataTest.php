<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function metadataAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

test('guests are redirected to login from the metadata pages', function () {
    $category = Category::factory()->create();
    $location = Location::factory()->create();

    $this->get(route('admin.metadata.kategori'))->assertRedirect(route('login'));
    $this->get(route('admin.metadata.kategori.create'))->assertRedirect(route('login'));
    $this->get(route('admin.metadata.kategori.edit', $category))->assertRedirect(route('login'));
    $this->post(route('admin.metadata.kategori.store'), ['name' => 'Elektronik'])->assertRedirect(route('login'));
    $this->put(route('admin.metadata.kategori.update', $category), ['name' => 'Elektronik'])->assertRedirect(route('login'));
    $this->delete(route('admin.metadata.kategori.destroy', $category))->assertRedirect(route('login'));

    $this->get(route('admin.metadata.lokasi'))->assertRedirect(route('login'));
    $this->get(route('admin.metadata.lokasi.create'))->assertRedirect(route('login'));
    $this->get(route('admin.metadata.lokasi.edit', $location))->assertRedirect(route('login'));
    $this->post(route('admin.metadata.lokasi.store'), ['name' => 'Gedung A'])->assertRedirect(route('login'));
    $this->put(route('admin.metadata.lokasi.update', $location), ['name' => 'Gedung A'])->assertRedirect(route('login'));
    $this->delete(route('admin.metadata.lokasi.destroy', $location))->assertRedirect(route('login'));
});

test('regular users cannot manage metadata', function () {
    $user = User::factory()->create(['role' => 'user']);
    $category = Category::factory()->create();

    $this->actingAs($user)->get(route('admin.metadata.kategori'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.metadata.lokasi'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.metadata.kategori.store'), ['name' => 'Elektronik'])->assertForbidden();
    $this->actingAs($user)->delete(route('admin.metadata.kategori.destroy', $category))->assertForbidden();

    $this->assertDatabaseCount('categories', 1);
});

test('the kategori index shows usage counts and hides delete for used categories', function () {
    $admin = metadataAdmin();
    $used = Category::factory()->create(['name' => 'Elektronik']);
    $unused = Category::factory()->create(['name' => 'Aksesoris']);
    Item::factory()->create(['category_id' => $used->category_id]);

    $this->actingAs($admin)
        ->get(route('admin.metadata.kategori'))
        ->assertOk()
        ->assertSee('Kelola Metadata')
        ->assertSee('Daftar Kategori')
        ->assertSee('Elektronik')
        ->assertSee('Aksesoris')
        ->assertSee('1 laporan')
        ->assertSee('0 laporan')
        ->assertSee('Tambah Kategori')
        ->assertSee(route('admin.metadata.kategori.create'), false)
        ->assertSee(route('admin.metadata.lokasi'), false)
        ->assertSee(route('admin.metadata.kategori.edit', $used), false)
        ->assertSee(route('admin.metadata.kategori.edit', $unused), false)
        ->assertSee('action="'.route('admin.metadata.kategori.destroy', $unused).'"', false)
        ->assertDontSee('action="'.route('admin.metadata.kategori.destroy', $used).'"', false)
        ->assertDontSee('Pencarian');
});

test('the lokasi index shows descriptions and hides delete for used locations', function () {
    $admin = metadataAdmin();
    $used = Location::factory()->create(['name' => 'Gedung A', 'description' => 'Gedung utama lantai 1']);
    $unused = Location::factory()->create(['name' => 'Gedung B', 'description' => null]);
    Item::factory()->create(['location_id' => $used->location_id]);

    $this->actingAs($admin)
        ->get(route('admin.metadata.lokasi'))
        ->assertOk()
        ->assertSee('Kelola Lokasi')
        ->assertSee('Daftar Lokasi')
        ->assertSee('Gedung A')
        ->assertSee('Gedung utama lantai 1')
        ->assertSee('Gedung B')
        ->assertSee('Tambah Lokasi')
        ->assertSee(route('admin.metadata.lokasi.edit', $used), false)
        ->assertSee('action="'.route('admin.metadata.lokasi.destroy', $unused).'"', false)
        ->assertDontSee('action="'.route('admin.metadata.lokasi.destroy', $used).'"', false);
});

test('admin can create a category with a generated slug and audit trail', function () {
    $admin = metadataAdmin();

    $this->actingAs($admin)
        ->get(route('admin.metadata.kategori.create'))
        ->assertOk()
        ->assertSee('Tambah Kategori');

    $this->actingAs($admin)
        ->post(route('admin.metadata.kategori.store'), ['name' => 'Kategori Baru'])
        ->assertRedirect(route('admin.metadata.kategori'))
        ->assertSessionHas('success', 'Kategori berhasil ditambahkan.');

    $category = Category::query()->where('name', 'Kategori Baru')->firstOrFail();

    expect($category->slug)->toBe('kategori-baru');

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->user_id,
        'entity_type' => 'category',
        'entity_id' => $category->category_id,
        'action' => 'create',
        'ip_address' => '127.0.0.1',
    ]);
});

test('category slugs get a numeric suffix when already taken', function () {
    $admin = metadataAdmin();
    Category::factory()->create(['name' => 'Dompet Hitam', 'slug' => 'dompet']);

    $this->actingAs($admin)
        ->post(route('admin.metadata.kategori.store'), ['name' => 'Dompet'])
        ->assertRedirect(route('admin.metadata.kategori'));

    $this->assertDatabaseHas('categories', ['name' => 'Dompet', 'slug' => 'dompet-2']);
});

test('creating a category rejects an empty name', function () {
    $admin = metadataAdmin();

    $this->actingAs($admin)
        ->post(route('admin.metadata.kategori.store'), ['name' => ''])
        ->assertSessionHasErrors(['name' => 'Nama kategori wajib diisi.']);

    $this->assertDatabaseCount('categories', 0);
});

test('creating a category rejects a name longer than 100 characters', function () {
    $admin = metadataAdmin();

    $this->actingAs($admin)
        ->post(route('admin.metadata.kategori.store'), ['name' => str_repeat('a', 101)])
        ->assertSessionHasErrors(['name' => 'Nama kategori maksimal 100 karakter.']);

    $this->assertDatabaseCount('categories', 0);
});

test('creating a category rejects a duplicate name', function () {
    $admin = metadataAdmin();
    Category::factory()->create(['name' => 'Elektronik']);

    $this->actingAs($admin)
        ->post(route('admin.metadata.kategori.store'), ['name' => 'Elektronik'])
        ->assertSessionHasErrors(['name' => 'Nama kategori sudah digunakan.']);

    $this->assertDatabaseCount('categories', 1);
});

test('admin can rename a category and the slug follows', function () {
    $admin = metadataAdmin();
    $category = Category::factory()->create(['name' => 'Kategori Lama', 'slug' => 'kategori-lama']);

    $this->actingAs($admin)
        ->get(route('admin.metadata.kategori.edit', $category))
        ->assertOk()
        ->assertSee('Edit Kategori')
        ->assertSee('Kategori Lama');

    $this->actingAs($admin)
        ->put(route('admin.metadata.kategori.update', $category), ['name' => 'Kategori Baru'])
        ->assertRedirect(route('admin.metadata.kategori'))
        ->assertSessionHas('success', 'Kategori berhasil diperbarui.');

    $this->assertDatabaseHas('categories', [
        'category_id' => $category->category_id,
        'name' => 'Kategori Baru',
        'slug' => 'kategori-baru',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->user_id,
        'entity_type' => 'category',
        'entity_id' => $category->category_id,
        'action' => 'update',
    ]);
});

test('renaming a category rejects a name used by another category', function () {
    $admin = metadataAdmin();
    Category::factory()->create(['name' => 'Elektronik']);
    $category = Category::factory()->create(['name' => 'Aksesoris']);

    $this->actingAs($admin)
        ->put(route('admin.metadata.kategori.update', $category), ['name' => 'Elektronik'])
        ->assertSessionHasErrors(['name' => 'Nama kategori sudah digunakan.']);

    $this->assertDatabaseHas('categories', ['category_id' => $category->category_id, 'name' => 'Aksesoris']);
});

test('admin can delete an unused category', function () {
    $admin = metadataAdmin();
    $category = Category::factory()->create(['name' => 'Aksesoris']);

    $this->actingAs($admin)
        ->delete(route('admin.metadata.kategori.destroy', $category))
        ->assertRedirect(route('admin.metadata.kategori'))
        ->assertSessionHas('success', 'Kategori berhasil dihapus.');

    $this->assertDatabaseMissing('categories', ['category_id' => $category->category_id]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->user_id,
        'entity_type' => 'category',
        'entity_id' => $category->category_id,
        'action' => 'delete',
    ]);
});

test('deleting a category used by a report is rejected', function () {
    $admin = metadataAdmin();
    $category = Category::factory()->create();
    $item = Item::factory()->create(['category_id' => $category->category_id]);
    $item->delete();

    $this->actingAs($admin)
        ->get(route('admin.metadata.kategori'))
        ->assertOk()
        ->assertDontSee('action="'.route('admin.metadata.kategori.destroy', $category).'"', false);

    $this->actingAs($admin)
        ->delete(route('admin.metadata.kategori.destroy', $category))
        ->assertSessionHasErrors(['name' => 'Kategori masih digunakan oleh 1 laporan dan tidak dapat dihapus.']);

    $this->assertDatabaseHas('categories', ['category_id' => $category->category_id]);
});

test('admin can create a location with a description and audit trail', function () {
    $admin = metadataAdmin();

    $this->actingAs($admin)
        ->get(route('admin.metadata.lokasi.create'))
        ->assertOk()
        ->assertSee('Tambah Lokasi');

    $this->actingAs($admin)
        ->post(route('admin.metadata.lokasi.store'), [
            'name' => 'Gedung Baru',
            'description' => 'Gedung utama lantai 2',
        ])
        ->assertRedirect(route('admin.metadata.lokasi'))
        ->assertSessionHas('success', 'Lokasi berhasil ditambahkan.');

    $location = Location::query()->where('name', 'Gedung Baru')->firstOrFail();

    expect($location->slug)->toBe('gedung-baru')
        ->and($location->description)->toBe('Gedung utama lantai 2');

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->user_id,
        'entity_type' => 'location',
        'entity_id' => $location->location_id,
        'action' => 'create',
        'ip_address' => '127.0.0.1',
    ]);
});

test('creating a location without a description is valid', function () {
    $admin = metadataAdmin();

    $this->actingAs($admin)
        ->post(route('admin.metadata.lokasi.store'), ['name' => 'Gedung Baru'])
        ->assertRedirect(route('admin.metadata.lokasi'))
        ->assertSessionHas('success', 'Lokasi berhasil ditambahkan.');

    $this->assertDatabaseHas('locations', ['name' => 'Gedung Baru', 'description' => null]);
});

test('creating a location rejects an empty or overlong name', function () {
    $admin = metadataAdmin();

    $this->actingAs($admin)
        ->post(route('admin.metadata.lokasi.store'), ['name' => ''])
        ->assertSessionHasErrors(['name' => 'Nama lokasi wajib diisi.']);

    $this->actingAs($admin)
        ->post(route('admin.metadata.lokasi.store'), ['name' => str_repeat('a', 151)])
        ->assertSessionHasErrors(['name' => 'Nama lokasi maksimal 150 karakter.']);

    $this->assertDatabaseCount('locations', 0);
});

test('creating a location rejects a duplicate name', function () {
    $admin = metadataAdmin();
    Location::factory()->create(['name' => 'Gedung A']);

    $this->actingAs($admin)
        ->post(route('admin.metadata.lokasi.store'), ['name' => 'Gedung A'])
        ->assertSessionHasErrors(['name' => 'Nama lokasi sudah digunakan.']);

    $this->assertDatabaseCount('locations', 1);
});

test('admin can update a location', function () {
    $admin = metadataAdmin();
    $location = Location::factory()->create(['name' => 'Gedung Lama', 'description' => 'Keterangan lama']);

    $this->actingAs($admin)
        ->get(route('admin.metadata.lokasi.edit', $location))
        ->assertOk()
        ->assertSee('Edit Lokasi')
        ->assertSee('Gedung Lama')
        ->assertSee('Keterangan lama');

    $this->actingAs($admin)
        ->put(route('admin.metadata.lokasi.update', $location), [
            'name' => 'Gedung Baru',
            'description' => 'Keterangan baru',
        ])
        ->assertRedirect(route('admin.metadata.lokasi'))
        ->assertSessionHas('success', 'Lokasi berhasil diperbarui.');

    $this->assertDatabaseHas('locations', [
        'location_id' => $location->location_id,
        'name' => 'Gedung Baru',
        'slug' => 'gedung-baru',
        'description' => 'Keterangan baru',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->user_id,
        'entity_type' => 'location',
        'entity_id' => $location->location_id,
        'action' => 'update',
    ]);
});

test('admin can delete an unused location', function () {
    $admin = metadataAdmin();
    $location = Location::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.metadata.lokasi.destroy', $location))
        ->assertRedirect(route('admin.metadata.lokasi'))
        ->assertSessionHas('success', 'Lokasi berhasil dihapus.');

    $this->assertDatabaseMissing('locations', ['location_id' => $location->location_id]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->user_id,
        'entity_type' => 'location',
        'entity_id' => $location->location_id,
        'action' => 'delete',
    ]);
});

test('deleting a location used by a report is rejected', function () {
    $admin = metadataAdmin();
    $location = Location::factory()->create();
    Item::factory()->create(['location_id' => $location->location_id]);

    $this->actingAs($admin)
        ->delete(route('admin.metadata.lokasi.destroy', $location))
        ->assertSessionHasErrors(['name' => 'Lokasi masih digunakan oleh 1 laporan dan tidak dapat dihapus.']);

    $this->assertDatabaseHas('locations', ['location_id' => $location->location_id]);
});
