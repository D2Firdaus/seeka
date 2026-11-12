<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('guests are redirected to login when visiting the settings page', function () {
    $this->get(route('pengaturan'))
        ->assertRedirect(route('login'));
});

test('users can view the settings page with notification and password forms', function () {
    $user = User::factory()->create([
        'notify_email' => true,
        'notify_whatsapp' => false,
    ]);

    $this->actingAs($user)
        ->get(route('pengaturan'))
        ->assertOk()
        ->assertSee('Notifikasi')
        ->assertSee('Ganti Kata Sandi')
        ->assertSee('Kata Sandi Saat Ini')
        ->assertSee('Konfirmasi Kata Sandi Baru');
});

test('google users do not see the password form', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);

    $this->actingAs($user)
        ->get(route('pengaturan'))
        ->assertOk()
        ->assertSee('Notifikasi')
        ->assertDontSee('Ganti Kata Sandi')
        ->assertDontSee('Kata Sandi Saat Ini');
});

test('guests are redirected to login when submitting settings updates', function () {
    $this->patch(route('pengaturan.notifikasi'), [
        'notify_email' => 1,
        'notify_whatsapp' => 0,
    ])->assertRedirect(route('login'));

    $this->patch(route('pengaturan.password'), [
        'current_password' => 'password',
        'password' => 'kata-sandi-baru',
        'password_confirmation' => 'kata-sandi-baru',
    ])->assertRedirect(route('login'));
});

test('users can toggle their notification preferences', function () {
    $user = User::factory()->create([
        'notify_email' => true,
        'notify_whatsapp' => false,
    ]);

    $this->actingAs($user)
        ->patch(route('pengaturan.notifikasi'), [
            'notify_email' => 0,
            'notify_whatsapp' => 1,
        ])
        ->assertRedirect(route('pengaturan'))
        ->assertSessionHas('success');

    $user->refresh();

    expect($user->notify_email)->toBeFalse()
        ->and($user->notify_whatsapp)->toBeTrue();
});

test('notification update validates boolean values', function () {
    $user = User::factory()->create([
        'notify_email' => true,
        'notify_whatsapp' => false,
    ]);

    $this->actingAs($user)
        ->patch(route('pengaturan.notifikasi'), [
            'notify_email' => 'bukan-boolean',
            'notify_whatsapp' => 1,
        ])
        ->assertSessionHasErrors('notify_email');

    $this->actingAs($user)
        ->patch(route('pengaturan.notifikasi'), [
            'notify_whatsapp' => 'bukan-boolean',
        ])
        ->assertSessionHasErrors(['notify_email', 'notify_whatsapp']);

    $user->refresh();

    expect($user->notify_email)->toBeTrue()
        ->and($user->notify_whatsapp)->toBeFalse();
});

test('users can change their password with the correct current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('pengaturan.password'), [
            'current_password' => 'password',
            'password' => 'kata-sandi-baru',
            'password_confirmation' => 'kata-sandi-baru',
        ])
        ->assertRedirect(route('pengaturan'))
        ->assertSessionHas('success');

    expect(Hash::check('kata-sandi-baru', $user->fresh()->password))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

test('password change rejects a wrong current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('pengaturan'))
        ->patch(route('pengaturan.password'), [
            'current_password' => 'kata-sandi-salah',
            'password' => 'kata-sandi-baru',
            'password_confirmation' => 'kata-sandi-baru',
        ])
        ->assertRedirect(route('pengaturan'))
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('password change validates minimum length and confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('pengaturan.password'), [
            'current_password' => 'password',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ])
        ->assertSessionHasErrors('password');

    $this->actingAs($user)
        ->patch(route('pengaturan.password'), [
            'current_password' => '',
            'password' => 'kata-sandi-baru',
            'password_confirmation' => 'kata-sandi-baru',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('google users cannot change their password through the settings page', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);

    $this->actingAs($user)
        ->patch(route('pengaturan.password'), [
            'current_password' => 'password',
            'password' => 'kata-sandi-baru',
            'password_confirmation' => 'kata-sandi-baru',
        ])
        ->assertRedirect(route('pengaturan'))
        ->assertSessionHasErrors('password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
