<?php

use App\Mail\KeputusanBandingMail;
use App\Mail\KeputusanKlaimMail;
use App\Models\Claim;
use App\Models\Dispute;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function verifikasiBandingFixtures(array $itemAttributes = []): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $claimant = User::factory()->create(['notify_email' => true]);
    $disputant = User::factory()->create(['notify_email' => true]);

    $item = Item::factory()->create(array_merge(['type' => 'found', 'status' => 'claimed'], $itemAttributes));
    $claim = Claim::factory()->approved($admin)->create([
        'found_item_id' => $item->item_id,
        'claimant_id' => $claimant->user_id,
    ]);
    $dispute = Dispute::factory()->create([
        'claim_id' => $claim->claim_id,
        'reported_by' => $disputant->user_id,
    ]);

    return [$admin, $claimant, $disputant, $item, $claim, $dispute];
}

test('guest is redirected to login from the dispute verification pages', function () {
    $dispute = Dispute::factory()->create();

    $this->get(route('admin.verifikasi.banding'))->assertRedirect(route('login'));
    $this->get(route('admin.verifikasi.banding.show', $dispute))->assertRedirect(route('login'));
    $this->patch(route('admin.verifikasi.banding.terima', $dispute))->assertRedirect(route('login'));
    $this->patch(route('admin.verifikasi.banding.tolak', $dispute))->assertRedirect(route('login'));
});

test('non admin users get forbidden on the dispute verification pages', function () {
    $user = User::factory()->create(['role' => 'user']);
    $dispute = Dispute::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.verifikasi.banding'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.verifikasi.banding.show', $dispute))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('admin.verifikasi.banding.terima', $dispute))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('admin.verifikasi.banding.tolak', $dispute), ['resolution_note' => 'Bukti banding tidak kuat.'])
        ->assertForbidden();
});

test('the dispute verification queue shows open disputes by default', function () {
    [$admin] = verifikasiBandingFixtures(['title' => 'Laptop Menunggu']);

    $decidedItem = Item::factory()->create(['type' => 'found', 'title' => 'Laptop Selesai', 'status' => 'claimed']);
    $decidedClaim = Claim::factory()->approved()->create(['found_item_id' => $decidedItem->item_id]);
    Dispute::factory()->resolved()->create(['claim_id' => $decidedClaim->claim_id]);

    $this->actingAs($admin)
        ->get(route('admin.verifikasi.banding'))
        ->assertOk()
        ->assertSee('Laptop Menunggu')
        ->assertDontSee('Laptop Selesai');
});

test('the dispute queue filters disputes by status and title', function () {
    [$admin] = verifikasiBandingFixtures(['title' => 'Dompet Hitam']);

    $kunciItem = Item::factory()->create(['type' => 'found', 'title' => 'Kunci Motor', 'status' => 'claimed']);
    $kunciClaim = Claim::factory()->approved()->create(['found_item_id' => $kunciItem->item_id]);
    Dispute::factory()->dismissed()->create(['claim_id' => $kunciClaim->claim_id]);

    $this->actingAs($admin)
        ->get(route('admin.verifikasi.banding', ['status' => 'dismissed']))
        ->assertOk()
        ->assertSee('Kunci Motor')
        ->assertDontSee('Dompet Hitam');

    $this->actingAs($admin)
        ->get(route('admin.verifikasi.banding', ['status' => 'all']))
        ->assertOk()
        ->assertSee('Dompet Hitam')
        ->assertSee('Kunci Motor');

    $this->actingAs($admin)
        ->get(route('admin.verifikasi.banding', ['status' => 'all', 'q' => 'Dompet']))
        ->assertOk()
        ->assertSee('Dompet Hitam')
        ->assertDontSee('Kunci Motor');
});

test('the dispute detail page shows the reason, the challenged claim and the hidden detail', function () {
    [$admin, , $disputant, , , $dispute] = verifikasiBandingFixtures([
        'title' => 'Tas Ransel Biru',
        'hidden_detail' => 'Ada stiker robot di saku dalam.',
    ]);

    $dispute->update(['reason' => 'Tas tersebut hilang di perpustakaan sebelum klaim diajukan.']);

    $this->actingAs($admin)
        ->get(route('admin.verifikasi.banding.show', $dispute))
        ->assertOk()
        ->assertSee('Tas Ransel Biru')
        ->assertSee('Ada stiker robot di saku dalam.')
        ->assertSee('Tas tersebut hilang di perpustakaan sebelum klaim diajukan.')
        ->assertSee($disputant->name)
        ->assertSee('Keputusan Banding');
});

test('the admin accepts a dispute and revokes the challenged claim', function () {
    Mail::fake();

    [$admin, $claimant, $disputant, $item, $claim, $dispute] = verifikasiBandingFixtures();

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.banding.terima', $dispute), ['resolution_note' => 'Bukti banding lebih kuat.'])
        ->assertRedirect(route('admin.verifikasi.banding'))
        ->assertSessionHas('success', 'Banding diterima, klaim terdahulu dicabut dan status barang kembali terbuka.');

    $this->assertDatabaseHas('disputes', [
        'dispute_id' => $dispute->dispute_id,
        'status' => 'resolved',
        'resolved_by' => $admin->user_id,
        'resolution_note' => 'Bukti banding lebih kuat.',
    ]);
    expect($dispute->fresh()->resolved_at)->not->toBeNull();

    $this->assertDatabaseHas('claims', [
        'claim_id' => $claim->claim_id,
        'status' => 'rejected',
        'verified_by' => $admin->user_id,
        'admin_note' => 'Klaim dicabut karena banding dari pihak lain diterima petugas.',
    ]);

    $this->assertDatabaseHas('items', [
        'item_id' => $item->item_id,
        'status' => 'open',
    ]);

    $this->assertDatabaseHas('item_logs', [
        'item_id' => $item->item_id,
        'user_id' => $admin->user_id,
        'old_status' => 'claimed',
        'new_status' => 'open',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'entity_type' => 'dispute',
        'entity_id' => $dispute->dispute_id,
        'action' => 'resolve',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'entity_type' => 'claim',
        'entity_id' => $claim->claim_id,
        'action' => 'reject',
    ]);

    Mail::assertSent(KeputusanBandingMail::class, fn (KeputusanBandingMail $mail) => $mail->hasTo($disputant->email) && $mail->decision === 'resolved');
    Mail::assertSent(KeputusanKlaimMail::class, fn (KeputusanKlaimMail $mail) => $mail->hasTo($claimant->email) && $mail->decision === 'revoked');
});

test('the admin rejects a dispute and the note is mandatory', function () {
    Mail::fake();

    [$admin, $claimant, $disputant, $item, $claim, $dispute] = verifikasiBandingFixtures();

    $this->actingAs($admin)
        ->from(route('admin.verifikasi.banding.show', $dispute))
        ->patch(route('admin.verifikasi.banding.tolak', $dispute))
        ->assertRedirect(route('admin.verifikasi.banding.show', $dispute))
        ->assertSessionHasErrors('resolution_note');

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.banding.tolak', $dispute), ['resolution_note' => 'Lemah'])
        ->assertSessionHasErrors('resolution_note');

    $this->assertDatabaseHas('disputes', ['dispute_id' => $dispute->dispute_id, 'status' => 'open']);

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.banding.tolak', $dispute), ['resolution_note' => 'Bukti banding tidak cukup kuat.'])
        ->assertRedirect(route('admin.verifikasi.banding'))
        ->assertSessionHas('success', 'Banding berhasil ditolak.');

    $this->assertDatabaseHas('disputes', [
        'dispute_id' => $dispute->dispute_id,
        'status' => 'dismissed',
        'resolved_by' => $admin->user_id,
        'resolution_note' => 'Bukti banding tidak cukup kuat.',
    ]);
    $this->assertDatabaseHas('claims', ['claim_id' => $claim->claim_id, 'status' => 'approved']);
    $this->assertDatabaseHas('items', ['item_id' => $item->item_id, 'status' => 'claimed']);

    Mail::assertSent(KeputusanBandingMail::class, fn (KeputusanBandingMail $mail) => $mail->hasTo($disputant->email) && $mail->decision === 'dismissed');
    Mail::assertNotSent(KeputusanKlaimMail::class);
});

test('dispute decision emails respect the notify_email preference', function () {
    Mail::fake();

    [$admin, , , , , $dispute] = verifikasiBandingFixtures();
    $dispute->reportedBy->update(['notify_email' => false]);

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.banding.terima', $dispute))
        ->assertRedirect(route('admin.verifikasi.banding'));

    $this->assertDatabaseHas('disputes', ['dispute_id' => $dispute->dispute_id, 'status' => 'resolved']);
    Mail::assertNotSent(KeputusanBandingMail::class);
});

test('the admin cannot resolve their own dispute', function () {
    [$admin] = verifikasiBandingFixtures();
    $ownDispute = Dispute::factory()->create(['reported_by' => $admin->user_id]);

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.banding.terima', $ownDispute))
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.banding.tolak', $ownDispute), ['resolution_note' => 'Bukti banding tidak cukup kuat.'])
        ->assertForbidden();
});

test('the admin cannot resolve a dispute that has been decided', function () {
    [$admin] = verifikasiBandingFixtures();
    $decidedDispute = Dispute::factory()->resolved()->create();

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.banding.terima', $decidedDispute))
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.banding.tolak', $decidedDispute), ['resolution_note' => 'Bukti banding tidak cukup kuat.'])
        ->assertForbidden();
});

test('a dispute cannot be accepted when the challenged claim is no longer approved', function () {
    [$admin, , , , $claim, $dispute] = verifikasiBandingFixtures();
    $claim->update(['status' => 'rejected']);

    $this->actingAs($admin)
        ->patch(route('admin.verifikasi.banding.terima', $dispute))
        ->assertRedirect(route('admin.verifikasi.banding.show', $dispute))
        ->assertSessionHasErrors('resolution_note');

    $this->assertDatabaseHas('disputes', ['dispute_id' => $dispute->dispute_id, 'status' => 'open']);
});

test('the dispute decision email renders the acceptance and rejection copy', function () {
    $dispute = Dispute::factory()->create();

    $acceptance = new KeputusanBandingMail($dispute->fresh(['claim.foundItem', 'reportedBy']), 'resolved', 'Bukti banding lebih kuat.');
    $acceptance->assertHasSubject('Pengajuan Banding Diterima');
    $acceptance->assertSeeInHtml('Banding Diterima');
    $acceptance->assertSeeInHtml('Bukti banding lebih kuat.');
    $acceptance->assertSeeInText('Banding Diterima');

    $rejection = new KeputusanBandingMail($dispute->fresh(['claim.foundItem', 'reportedBy']), 'dismissed');
    $rejection->assertHasSubject('Pengajuan Banding Ditolak');
    $rejection->assertSeeInHtml('Banding Ditolak');
    $rejection->assertDontSeeInHtml('Catatan petugas');
});
