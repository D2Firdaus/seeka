<?php

use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest is redirected to login from the my claims pages', function () {
    $claim = Claim::factory()->create();

    $this->get(route('klaim.saya'))->assertRedirect(route('login'));
    $this->get(route('klaim.edit', $claim))->assertRedirect(route('login'));
    $this->put(route('klaim.update', $claim))->assertRedirect(route('login'));
    $this->delete(route('klaim.destroy', $claim))->assertRedirect(route('login'));
});

test('the index page shows the page heading without a sub-navbar', function () {
    $user = User::factory()->create();
    Claim::factory()->create(['claimant_id' => $user->user_id]);

    $this->actingAs($user)
        ->get(route('klaim.saya'))
        ->assertOk()
        ->assertSee('<h1 class="text-display font-display mb-[1.15em]">Klaim Saya</h1>', false)
        ->assertDontSee('Pencarian');
});

test('the index page only shows the authenticated user claims', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $userItem = Item::factory()->create(['type' => 'found', 'title' => 'Tas Ransel Milik Saya']);
    $otherItem = Item::factory()->create(['type' => 'found', 'title' => 'Payung Milik Orang Lain']);

    Claim::factory()->create(['found_item_id' => $userItem->item_id, 'claimant_id' => $user->user_id]);
    Claim::factory()->create(['found_item_id' => $otherItem->item_id, 'claimant_id' => $other->user_id]);

    $this->actingAs($user)
        ->get(route('klaim.saya'))
        ->assertOk()
        ->assertSee('Tas Ransel Milik Saya')
        ->assertDontSee('Payung Milik Orang Lain');
});

test('the index page shows claim status badges', function () {
    $user = User::factory()->create();

    Claim::factory()->create(['claimant_id' => $user->user_id, 'status' => 'pending']);
    Claim::factory()->approved()->create(['claimant_id' => $user->user_id]);
    Claim::factory()->rejected()->create(['claimant_id' => $user->user_id]);

    $this->actingAs($user)
        ->get(route('klaim.saya'))
        ->assertOk()
        ->assertSee('Menunggu Verifikasi')
        ->assertSee('Disetujui')
        ->assertSee('Ditolak');
});

test('the index page filters by claim status', function () {
    $user = User::factory()->create();

    $pendingItem = Item::factory()->create(['type' => 'found', 'title' => 'Dompet Hitam']);
    $approvedItem = Item::factory()->create(['type' => 'found', 'title' => 'Kunci Motor']);

    Claim::factory()->create(['found_item_id' => $pendingItem->item_id, 'claimant_id' => $user->user_id, 'status' => 'pending']);
    Claim::factory()->approved()->create(['found_item_id' => $approvedItem->item_id, 'claimant_id' => $user->user_id]);

    $this->actingAs($user)
        ->get(route('klaim.saya', ['status' => 'pending']))
        ->assertOk()
        ->assertSee('Dompet Hitam')
        ->assertDontSee('Kunci Motor');

    $this->actingAs($user)
        ->get(route('klaim.saya', ['status' => 'approved']))
        ->assertOk()
        ->assertSee('Kunci Motor')
        ->assertDontSee('Dompet Hitam');
});

test('the index page searches by found item title', function () {
    $user = User::factory()->create();

    $item = Item::factory()->create(['type' => 'found', 'title' => 'Kunci Motor Beat']);
    $otherItem = Item::factory()->create(['type' => 'found', 'title' => 'Payung Transparan']);

    Claim::factory()->create(['found_item_id' => $item->item_id, 'claimant_id' => $user->user_id]);
    Claim::factory()->create(['found_item_id' => $otherItem->item_id, 'claimant_id' => $user->user_id]);

    $this->actingAs($user)
        ->get(route('klaim.saya', ['q' => 'Kunci']))
        ->assertOk()
        ->assertSee('Kunci Motor Beat')
        ->assertDontSee('Payung Transparan');
});

test('the index page shows an empty state without claims', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('klaim.saya'))
        ->assertOk()
        ->assertSee('Anda belum mengajukan klaim apa pun.');

    $this->actingAs($user)
        ->get(route('klaim.saya', ['q' => 'tidak ada']))
        ->assertOk()
        ->assertSee('Belum ada klaim yang cocok dengan filter yang dipilih.');
});

test('pending claims show edit and withdraw actions on the index', function () {
    $user = User::factory()->create();

    $claim = Claim::factory()->create(['claimant_id' => $user->user_id, 'status' => 'pending']);

    $this->actingAs($user)
        ->get(route('klaim.saya'))
        ->assertOk()
        ->assertSee(route('klaim.edit', $claim))
        ->assertSee(route('klaim.destroy', $claim))
        ->assertSee('aria-label="Ubah Klaim"', false)
        ->assertSee('aria-label="Tarik Klaim"', false);
});

test('processed claims show no actions on the index', function () {
    $user = User::factory()->create();

    Claim::factory()->approved()->create(['claimant_id' => $user->user_id]);

    $this->actingAs($user)
        ->get(route('klaim.saya'))
        ->assertOk()
        ->assertDontSee('aria-label="Ubah Klaim"', false)
        ->assertDontSee('aria-label="Tarik Klaim"', false);
});

test('the claimant can view the edit form for a pending claim', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['type' => 'found', 'title' => 'Payung Transparan']);

    $claim = Claim::factory()->create([
        'found_item_id' => $item->item_id,
        'claimant_id' => $user->user_id,
        'status' => 'pending',
        'proof_description' => 'Bukti lama yang sudah saya tulis.',
    ]);

    $this->actingAs($user)
        ->get(route('klaim.edit', $claim))
        ->assertOk()
        ->assertSee('Ubah Klaim')
        ->assertSee('Payung Transparan')
        ->assertSee('Bukti lama yang sudah saya tulis.');
});

test('the claimant can update a pending claim', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create(['type' => 'found']);

    $claim = Claim::factory()->create([
        'found_item_id' => $item->item_id,
        'claimant_id' => $user->user_id,
        'status' => 'pending',
        'proof_description' => 'Bukti lama yang sudah saya tulis.',
    ]);

    $this->actingAs($user)
        ->put(route('klaim.update', $claim), [
            'proof_description' => 'Bukti baru yang lebih lengkap dan detail.',
            'declaration' => '1',
        ])
        ->assertRedirect(route('klaim.saya'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('claims', [
        'claim_id' => $claim->claim_id,
        'proof_description' => 'Bukti baru yang lebih lengkap dan detail.',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->user_id,
        'entity_type' => 'claim',
        'entity_id' => $claim->claim_id,
        'action' => 'update',
    ]);
});

test('claim update fails when proof_description is missing or too short', function () {
    $user = User::factory()->create();
    $claim = Claim::factory()->create(['claimant_id' => $user->user_id, 'status' => 'pending']);

    $this->actingAs($user)
        ->put(route('klaim.update', $claim), [
            'proof_description' => 'pendek',
            'declaration' => '1',
        ])
        ->assertSessionHasErrors('proof_description');

    $this->actingAs($user)
        ->put(route('klaim.update', $claim), [
            'declaration' => '1',
        ])
        ->assertSessionHasErrors('proof_description');
});

test('users cannot edit or withdraw claims that are not theirs', function () {
    $claim = Claim::factory()->create(['status' => 'pending']);
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('klaim.edit', $claim))->assertForbidden();

    $this->actingAs($other)->put(route('klaim.update', $claim), [
        'proof_description' => 'Saya coba mengubah klaim orang lain.',
        'declaration' => '1',
    ])->assertForbidden();

    $this->actingAs($other)->delete(route('klaim.destroy', $claim))->assertForbidden();
});

test('processed claims cannot be edited or withdrawn', function (string $status) {
    $user = User::factory()->create();
    $claim = Claim::factory()->state(['status' => $status])->create([
        'claimant_id' => $user->user_id,
    ]);

    $this->actingAs($user)->get(route('klaim.edit', $claim))->assertForbidden();

    $this->actingAs($user)->put(route('klaim.update', $claim), [
        'proof_description' => 'Saya coba mengubah klaim yang sudah diproses.',
        'declaration' => '1',
    ])->assertForbidden();

    $this->actingAs($user)->delete(route('klaim.destroy', $claim))->assertForbidden();
})->with(['approved', 'rejected']);

test('the claimant can withdraw a pending claim', function () {
    $user = User::factory()->create();
    $claim = Claim::factory()->create(['claimant_id' => $user->user_id, 'status' => 'pending']);

    $this->actingAs($user)
        ->delete(route('klaim.destroy', $claim))
        ->assertRedirect(route('klaim.saya'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('claims', ['claim_id' => $claim->claim_id]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->user_id,
        'entity_type' => 'claim',
        'entity_id' => $claim->claim_id,
        'action' => 'delete',
    ]);
});

test('the navbar links to the my claims page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('klaim.saya'));
});
