<?php

use App\Mail\KeputusanKlaimMail;
use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('guest is redirected to login from the claim verification pages', function () {
    $claim = Claim::factory()->create();

    $this->get(route('admin.verifikasi.klaim'))->assertRedirect(route('login'));
    $this->get(route('admin.verifikasi.klaim.show', $claim))->assertRedirect(route('login'));
    $this->patch(route('admin.verifikasi.klaim.setujui', $claim))->assertRedirect(route('login'));
    $this->patch(route('admin.verifikasi.klaim.tolak', $claim))->assertRedirect(route('login'));
});

test('non admin users get forbidden on the claim verification pages', function () {
    $user = User::factory()->create(['role' => 'user']);
    $claim = Claim::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.verifikasi.klaim'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.verifikasi.klaim.show', $claim))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('admin.verifikasi.klaim.setujui', $claim))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('admin.verifikasi.klaim.tolak', $claim), ['admin_note' => 'Tidak sesuai dengan ciri khusus.'])
        ->assertForbidden();
});

test('the claim verification queue shows pending claims by default', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $pendingItem = Item::factory()->create(['type' => 'found', 'title' => 'Laptop Menunggu']);
    Claim::factory()->create(['found_item_id' => $pendingItem->item_id, 'status' => 'pending']);

    $decidedItem = Item::factory()->create(['type' => 'found', 'title' => 'Laptop Selesai']);
    Claim::factory()->approved()->create(['found_item_id' => $decidedItem->item_id]);

    $this->actingAs($admin)
        ->get(route('admin.verifikasi.klaim'))
        ->assertOk()
        ->assertSee('Laptop Menunggu')
        ->assertDontSee('Laptop Selesai');
});

test('the claim queue filters claims by status and title', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $dompetItem = Item::factory()->create(['type' => 'found', 'title' => 'Dompet Hitam']);
    Claim::factory()->create(['found_item_id' => $dompetItem->item_id, 'status' => 'pending']);

    $kunciItem = Item::factory()->create(['type' => 'found', 'title' => 'Kunci Motor']);
    Claim::factory()->approved()->create(['found_item_id' => $kunciItem->item_id]);

    $this->actingAs($admin)
        ->get(route('admin.verifikasi.klaim', ['status' => 'approved']))
        ->assertOk()
        ->assertSee('Kunci Motor')
        ->assertDontSee('Dompet Hitam');

    $this->actingAs($admin)
        ->get(route('admin.verifikasi.klaim', ['status' => 'all']))
        ->assertOk()
        ->assertSee('Dompet Hitam')
        ->assertSee('Kunci Motor');

    $this->actingAs($admin)
        ->get(route('admin.verifikasi.klaim', ['status' => 'all', 'q' => 'Dompet']))
        ->assertOk()
        ->assertSee('Dompet Hitam')
        ->assertDontSee('Kunci Motor');
});

test('the claim detail page shows the hidden detail alongside the claim proof', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $claimant = User::factory()->create(['name' => 'Budi Santoso']);

    $item = Item::factory()->create([
        'type' => 'found',
        'title' => 'Tas Ransel Biru',
        'hidden_detail' => 'Ada stiker robot di saku dalam.',
    ]);
    $claim = Claim::factory()->create([
        'found_item_id' => $item->item_id,
        'claimant_id' => $claimant->user_id,
        'proof_description' => 'Isi tas berisi buku catatan dan charger.',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.verifikasi.klaim.show', $claim))
        ->assertOk()
        ->assertSee('Tas Ransel Biru')
        ->assertSee('Ada stiker robot di saku dalam.')
        ->assertSee('Isi tas berisi buku catatan dan charger.')
        ->assertSee('Budi Santoso')
        ->assertSee('Keputusan Verifikasi');
});

test('the verification pages show the sub navigation without the main tab bar', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $claim = Claim::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.verifikasi.klaim'))
        ->assertOk()
        ->assertSee('<h1 class="text-display font-display mb-[1.15em]">Verifikasi Laporan</h1>', false)
        ->assertSee('Verifikasi Klaim')
        ->assertSee('Verifikasi Banding')
        ->assertSee(route('admin.verifikasi.banding'))
        ->assertDontSee('Pencarian');

    $this->actingAs($admin)
        ->get(route('admin.verifikasi.klaim.show', $claim))
        ->assertOk()
        ->assertSee(route('admin.verifikasi.banding'))
        ->assertDontSee('Pencarian');
});

test('the admin approves a pending claim and locks the item for that claim', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $claimant = User::factory()->create(['notify_email' => true]);
    $item = Item::factory()->create(['type' => 'found', 'status' => 'open']);
    $claim = Claim::factory()->create([
        'found_item_id' => $item->item_id,
        'claimant_id' => $claimant->user_id,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.klaim.setujui', $claim), ['admin_note' => 'Ciri khusus cocok.'])
        ->assertRedirect(route('admin.verifikasi.klaim'))
        ->assertSessionHas('success', 'Klaim berhasil disetujui.');

    $this->assertDatabaseHas('claims', [
        'claim_id' => $claim->claim_id,
        'status' => 'approved',
        'verified_by' => $admin->user_id,
        'admin_note' => 'Ciri khusus cocok.',
    ]);
    expect($claim->fresh()->verified_at)->not->toBeNull();

    $this->assertDatabaseHas('items', [
        'item_id' => $item->item_id,
        'status' => 'claimed',
    ]);

    $this->assertDatabaseHas('item_logs', [
        'item_id' => $item->item_id,
        'user_id' => $admin->user_id,
        'old_status' => 'open',
        'new_status' => 'claimed',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->user_id,
        'entity_type' => 'claim',
        'entity_id' => $claim->claim_id,
        'action' => 'approve',
    ]);

    Mail::assertSent(KeputusanKlaimMail::class, fn (KeputusanKlaimMail $mail) => $mail->hasTo($claimant->email) && $mail->decision === 'approved');
});

test('approving a claim automatically rejects the other pending claims on the same item', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $item = Item::factory()->create(['type' => 'found', 'status' => 'open']);
    $winningClaim = Claim::factory()->create(['found_item_id' => $item->item_id, 'status' => 'pending']);
    $otherClaimant = User::factory()->create(['notify_email' => true]);
    $otherClaim = Claim::factory()->create([
        'found_item_id' => $item->item_id,
        'claimant_id' => $otherClaimant->user_id,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.klaim.setujui', $winningClaim))
        ->assertRedirect(route('admin.verifikasi.klaim'));

    $this->assertDatabaseHas('claims', [
        'claim_id' => $otherClaim->claim_id,
        'status' => 'rejected',
        'verified_by' => $admin->user_id,
        'admin_note' => 'Klaim lain untuk barang ini telah disetujui petugas.',
    ]);

    Mail::assertSent(KeputusanKlaimMail::class, fn (KeputusanKlaimMail $mail) => $mail->hasTo($otherClaimant->email) && $mail->decision === 'rejected');
});

test('the admin rejects a pending claim and the note is mandatory', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $claimant = User::factory()->create(['notify_email' => true]);
    $claim = Claim::factory()->create(['claimant_id' => $claimant->user_id, 'status' => 'pending']);

    $this->actingAs($admin)
        ->from(route('admin.verifikasi.klaim.show', $claim))
        ->patch(route('admin.verifikasi.klaim.tolak', $claim))
        ->assertRedirect(route('admin.verifikasi.klaim.show', $claim))
        ->assertSessionHasErrors('admin_note');

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.klaim.tolak', $claim), ['admin_note' => 'Terlalu'])
        ->assertSessionHasErrors('admin_note');

    $this->assertDatabaseHas('claims', ['claim_id' => $claim->claim_id, 'status' => 'pending']);

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.klaim.tolak', $claim), ['admin_note' => 'Ciri khusus tidak cocok dengan bukti.'])
        ->assertRedirect(route('admin.verifikasi.klaim'))
        ->assertSessionHas('success', 'Klaim berhasil ditolak.');

    $this->assertDatabaseHas('claims', [
        'claim_id' => $claim->claim_id,
        'status' => 'rejected',
        'verified_by' => $admin->user_id,
        'admin_note' => 'Ciri khusus tidak cocok dengan bukti.',
    ]);

    Mail::assertSent(KeputusanKlaimMail::class, fn (KeputusanKlaimMail $mail) => $mail->hasTo($claimant->email) && $mail->decision === 'rejected');
});

test('claim decision emails respect the notify_email preference', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $claimant = User::factory()->create(['notify_email' => false]);
    $claim = Claim::factory()->create(['claimant_id' => $claimant->user_id, 'status' => 'pending']);

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.klaim.setujui', $claim))
        ->assertRedirect(route('admin.verifikasi.klaim'));

    $this->assertDatabaseHas('claims', ['claim_id' => $claim->claim_id, 'status' => 'approved']);
    Mail::assertNothingSent();
});

test('the admin cannot verify their own claim', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $item = Item::factory()->create(['type' => 'found', 'status' => 'open']);
    $claim = Claim::factory()->create([
        'found_item_id' => $item->item_id,
        'claimant_id' => $admin->user_id,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.klaim.setujui', $claim))
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.klaim.tolak', $claim), ['admin_note' => 'Ciri khusus tidak cocok.'])
        ->assertForbidden();
});

test('the admin cannot verify a claim that has been decided', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $claim = Claim::factory()->approved()->create();

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.klaim.setujui', $claim))
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.klaim.tolak', $claim), ['admin_note' => 'Ciri khusus tidak cocok.'])
        ->assertForbidden();
});

test('a claim cannot be approved when the item is no longer open', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $item = Item::factory()->create(['type' => 'found', 'status' => 'closed']);
    $claim = Claim::factory()->create(['found_item_id' => $item->item_id, 'status' => 'pending']);

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.klaim.setujui', $claim))
        ->assertRedirect(route('admin.verifikasi.klaim.show', $claim))
        ->assertSessionHasErrors('admin_note');

    $this->assertDatabaseHas('claims', ['claim_id' => $claim->claim_id, 'status' => 'pending']);
});

test('the claim decision email renders the approval and rejection copy', function () {
    $claim = Claim::factory()->create();

    $approval = new KeputusanKlaimMail($claim->fresh(['claimant', 'foundItem']), 'approved', 'Ciri khusus cocok.');
    $approval->assertHasSubject('Klaim Barang Temuan Disetujui');
    $approval->assertSeeInHtml('Klaim Disetujui');
    $approval->assertSeeInHtml('Ciri khusus cocok.');
    $approval->assertSeeInText('Klaim Disetujui');

    $rejection = new KeputusanKlaimMail($claim->fresh(['claimant', 'foundItem']), 'rejected');
    $rejection->assertHasSubject('Klaim Barang Temuan Tidak Disetujui');
    $rejection->assertSeeInHtml('Klaim Tidak Disetujui');
    $rejection->assertDontSeeInHtml('Catatan petugas');
});
