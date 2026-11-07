<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('register page renders the registration form', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Buat Akun SEEKA')
        ->assertSee('Nama Lengkap')
        ->assertSee('Saya Menyetujui Syarat dan Ketentuan serta Kebijakan Privasi');
});

test('registration creates the account and logs the user in', function () {
    $response = $this->post(route('register'), [
        'name' => 'Dede Firdaus',
        'email' => '1234567890@student.unsika.ac.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'terms' => '1',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'name' => 'Dede Firdaus',
        'email' => '1234567890@student.unsika.ac.id',
        'nim_nip' => '1234567890',
        'email_verified_at' => null,
        'notify_email' => true,
        'notify_whatsapp' => false,
    ]);
});

test('registration sends the activation email to the new user', function () {
    Notification::fake();

    $this->post(route('register'), [
        'name' => 'Dede Firdaus',
        'email' => 'dede@unsika.ac.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'terms' => '1',
    ])->assertRedirect(route('verification.notice'));

    Notification::assertSentTo(User::first(), VerifyEmail::class);
});

test('registration leaves nim_nip empty for a name email prefix', function () {
    $response = $this->post(route('register'), [
        'name' => 'Budi Santoso',
        'email' => 'budi@unsika.ac.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'terms' => '1',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertDatabaseHas('users', [
        'email' => 'budi@unsika.ac.id',
        'nim_nip' => null,
    ]);
});

test('registration accepts unsika.ac.id campus emails', function (string $email) {
    $response = $this->post(route('register'), [
        'name' => 'Dede Firdaus',
        'email' => $email,
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'terms' => '1',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => $email]);
})->with([
    'domain utama' => 'budi@unsika.ac.id',
    'subdomain mahasiswa' => '1234567890@student.unsika.ac.id',
    'subdomain fakultas' => 'budi@fakultas.unsika.ac.id',
]);

test('registration rejects emails outside unsika.ac.id', function (string $email) {
    $response = $this->from(route('register'))->post(route('register'), [
        'name' => 'Dede Firdaus',
        'email' => $email,
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'terms' => '1',
    ]);

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toBe('Email harus menggunakan domain unsika.ac.id.');
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
})->with([
    'email publik' => 'dede@gmail.com',
    'mirip domain' => 'dede@evilunsika.ac.id',
    'domain di belakang' => 'dede@unsika.ac.id.evil.com',
    'tanpa pemisah' => 'dede@unsikaac.id',
]);

test('registration requires name, email, password, and terms agreement', function () {
    $response = $this->from(route('register'))->post(route('register'), []);

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['name', 'email', 'password', 'terms']);
    expect(session('errors')->first('name'))->toBe('Nama wajib diisi.')
        ->and(session('errors')->first('email'))->toBe('Email wajib diisi.')
        ->and(session('errors')->first('password'))->toBe('Kata sandi wajib diisi.');
    $this->assertGuest();
});

test('registration requires agreement to the terms and privacy policy', function () {
    $response = $this->from(route('register'))->post(route('register'), [
        'name' => 'Dede Firdaus',
        'email' => 'dede@unsika.ac.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ]);

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors('terms');
    expect(session('errors')->first('terms'))->toBe('Anda harus menyetujui syarat dan ketentuan serta kebijakan privasi.');
    $this->assertGuest();
});

test('registration rejects an already registered email', function () {
    User::factory()->create(['email' => 'budi@unsika.ac.id']);

    $response = $this->from(route('register'))->post(route('register'), [
        'name' => 'Budi Santoso',
        'email' => 'budi@unsika.ac.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'terms' => '1',
    ]);

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toBe('Email sudah terdaftar.');
    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});

test('registration rejects a password confirmation mismatch', function () {
    $response = $this->from(route('register'))->post(route('register'), [
        'name' => 'Dede Firdaus',
        'email' => 'dede@unsika.ac.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia456',
        'terms' => '1',
    ]);

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors('password');
    expect(session('errors')->first('password'))->toBe('Konfirmasi kata sandi tidak cocok.');
    $this->assertGuest();
});

test('registration rejects a too short password', function () {
    $response = $this->from(route('register'))->post(route('register'), [
        'name' => 'Dede Firdaus',
        'email' => 'dede@unsika.ac.id',
        'password' => 'abc123',
        'password_confirmation' => 'abc123',
        'terms' => '1',
    ]);

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors('password');
    expect(session('errors')->first('password'))->toBe('Kata sandi minimal 8 karakter.');
    $this->assertGuest();
});

test('re-registering an unverified account continues to the verification page', function () {
    $user = User::factory()->unverified()->create([
        'name' => 'Budi',
        'email' => 'budi@unsika.ac.id',
    ]);

    $response = $this->post(route('register'), [
        'name' => 'Budi Baru',
        'email' => 'budi@unsika.ac.id',
        'password' => 'rahasia999',
        'password_confirmation' => 'rahasia999',
        'terms' => '1',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseHas('users', [
        'user_id' => $user->user_id,
        'name' => 'Budi Baru',
    ]);
    expect(Hash::check('rahasia999', $user->fresh()->password))->toBeTrue();
});

test('re-registering an unverified account within a minute does not resend the activation email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create([
        'email' => 'budi@unsika.ac.id',
        'verification_sent_at' => now(),
    ]);

    $this->post(route('register'), [
        'name' => 'Budi Santoso',
        'email' => 'budi@unsika.ac.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'terms' => '1',
    ])->assertRedirect(route('verification.notice'));

    Notification::assertNotSentTo($user, VerifyEmail::class);
});

test('re-registering an unverified account after a minute resends the activation email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create([
        'email' => 'budi@unsika.ac.id',
        'verification_sent_at' => now()->subSeconds(61),
    ]);

    $this->post(route('register'), [
        'name' => 'Budi Santoso',
        'email' => 'budi@unsika.ac.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'terms' => '1',
    ])->assertRedirect(route('verification.notice'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('registration attempts are rate limited', function () {
    foreach (range(1, 10) as $attempt) {
        $this->post(route('register'), [])->assertStatus(302);
    }

    $this->post(route('register'), [])->assertStatus(429);
});
