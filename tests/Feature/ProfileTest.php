<?php

use App\Models\Claim;
use App\Models\Dispute;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

uses(RefreshDatabase::class);

test('guests are redirected to login when visiting the profile page', function () {
    $this->get(route('profile.show'))
        ->assertRedirect(route('login'));
});

test('users can view their own profile with identity and stats', function () {
    $user = User::factory()->create([
        'name' => 'Dede Firdaus',
        'email' => 'dede@mahasiswa.unsika.ac.id',
        'nim_nip' => '2110512001',
        'phone' => '6281234567890',
    ]);

    Item::factory()->create(['user_id' => $user->user_id, 'type' => 'lost']);
    Item::factory()->create(['user_id' => $user->user_id, 'type' => 'found']);

    $response = $this->actingAs($user)
        ->get(route('profile.show'));

    $response->assertOk()
        ->assertSee('Profil Saya')
        ->assertSee('Dede Firdaus')
        ->assertSee('dede@mahasiswa.unsika.ac.id')
        ->assertSee('2110512001')
        ->assertSee('6281234567890')
        ->assertSee('Terverifikasi')
        ->assertSee('Pengguna')
        ->assertDontSee('Pencarian');

    $response->assertSee('1 kehilangan · 1 penemuan');
});

test('profile page does not leak other users data', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create(['name' => 'Orang Lain']);

    $otherItem = Item::factory()->create([
        'user_id' => $otherUser->user_id,
        'title' => 'Barang Milik Orang Lain',
    ]);

    Item::factory()->create([
        'user_id' => $user->user_id,
        'title' => 'Barang Saya Sendiri',
    ]);

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Barang Saya Sendiri')
        ->assertDontSee('Barang Milik Orang Lain')
        ->assertDontSee('Orang Lain');
});

test('profile page lists recent claims with status', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['type' => 'found', 'title' => 'Tas Ransel Eiger']);

    Claim::factory()->create([
        'found_item_id' => $item->item_id,
        'claimant_id' => $user->user_id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Tas Ransel Eiger')
        ->assertSee('Menunggu');
});

test('profile page lists recent disputes with status', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['type' => 'found', 'title' => 'Dompet Kulit']);
    $claim = Claim::factory()->create([
        'found_item_id' => $item->item_id,
        'claimant_id' => User::factory()->create()->user_id,
    ]);

    Dispute::factory()->create([
        'claim_id' => $claim->claim_id,
        'reported_by' => $user->user_id,
        'status' => 'open',
    ]);

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Banding Terbaru')
        ->assertSee('Dompet Kulit')
        ->assertSee('Terbuka');
});

test('profile page shows empty states for a user with no activity', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Belum ada laporan.')
        ->assertSee('Belum ada pengajuan klaim.')
        ->assertDontSee('Banding Terbaru');
});

test('profile page shows unverified badge for users without verified email', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Belum Diverifikasi');
});

test('profile page shows notification preferences summary', function () {
    $user = User::factory()->create([
        'notify_email' => true,
        'notify_whatsapp' => false,
    ]);

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Notifikasi')
        ->assertSee('Aktif');
});

test('guests are redirected to login when submitting profile update', function () {
    $this->patch(route('profile.update'), [
        'name' => 'Nama Baru',
    ])
        ->assertRedirect(route('login'));
});

test('users can update their name and phone number', function () {
    $user = User::factory()->create([
        'name' => 'Nama Lama',
        'phone' => null,
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'phone' => '6281234567890',
        ])
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'user_id' => $user->user_id,
        'name' => 'Nama Baru',
        'phone' => '6281234567890',
    ]);
});

test('profile update ignores protected fields even when submitted', function () {
    $user = User::factory()->create([
        'name' => 'Nama Asli',
        'email' => 'asli@kampus.ac.id',
        'nim_nip' => '2110512001',
        'role' => 'user',
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'palsu@kampus.ac.id',
            'nim_nip' => '999999999',
            'role' => 'admin',
        ])
        ->assertRedirect(route('profile.show'));

    $this->assertDatabaseHas('users', [
        'user_id' => $user->user_id,
        'name' => 'Nama Baru',
        'email' => 'asli@kampus.ac.id',
        'nim_nip' => '2110512001',
        'role' => 'user',
    ]);
});

test('profile update validates name and phone number', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => '',
            'phone' => 'bukan-angka',
        ])
        ->assertSessionHasErrors(['name', 'phone']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nama Valid',
            'phone' => '',
        ])
        ->assertRedirect(route('profile.show'));

    $this->assertDatabaseHas('users', [
        'user_id' => $user->user_id,
        'name' => 'Nama Valid',
        'phone' => null,
    ]);
});

test('profile page shows the edit form when validation fails', function () {
    $user = User::factory()->create(['name' => 'Nama Asli']);

    $this->actingAs($user)
        ->from(route('profile.show'))
        ->patch(route('profile.update'), [
            'name' => '',
        ])
        ->assertSessionHasErrors('name')
        ->assertRedirect(route('profile.show'));

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee('profile-edit-toggle', false)
        ->assertSee('value=""', false);
});

test('users can fill their empty nim nip through profile update', function () {
    $user = User::factory()->create(['nim_nip' => null]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'nim_nip' => '2110512099',
        ])
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'user_id' => $user->user_id,
        'nim_nip' => '2110512099',
    ]);
});

test('nim nip update validates format and uniqueness when still empty', function () {
    $user = User::factory()->create(['nim_nip' => null]);
    User::factory()->create(['nim_nip' => '1234567890']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'nim_nip' => 'abc123',
        ])
        ->assertSessionHasErrors('nim_nip');

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'nim_nip' => '1234567890',
        ])
        ->assertSessionHasErrors('nim_nip');
});

test('nim nip field is editable while empty and readonly after filled', function () {
    $emptyUser = User::factory()->create(['nim_nip' => null]);

    $html = $this->actingAs($emptyUser)
        ->get(route('profile.show'))
        ->assertOk()
        ->getContent();

    preg_match('/<input id="profile-nim-nip".*?>/s', $html, $emptyTag);
    expect($emptyTag[0] ?? '')->not->toContain('readonly');

    $filledUser = User::factory()->create(['nim_nip' => '2110512001']);

    $html = $this->actingAs($filledUser)
        ->get(route('profile.show'))
        ->assertOk()
        ->getContent();

    preg_match('/<input id="profile-nim-nip".*?>/s', $html, $filledTag);
    expect($filledTag[0] ?? '')->toContain('readonly');
});

test('nim nip unique rule excludes the current user by user_id not id', function () {
    $user = User::factory()->create(['nim_nip' => null]);

    DB::enableQueryLog();

    try {
        validator(['nim_nip' => '2110512001'], [
            'nim_nip' => [Rule::unique('users', 'nim_nip')->ignore($user)],
        ])->validate();
    } catch (Throwable $e) {
        // ValidationException tidak relevan — yang diuji adalah kolom exclude-nya.
    }

    $queries = collect(DB::getQueryLog())->pluck('query')->implode("\n");

    expect($queries)->toContain('user_id')
        ->and($queries)->not->toContain('"id" <>');
});
