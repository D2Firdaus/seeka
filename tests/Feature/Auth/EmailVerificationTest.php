<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('guests without a pending registration are redirected to register', function () {
    $this->get(route('verification.notice'))
        ->assertRedirect(route('register'));
});

test('the waiting page shows the registered email from the session', function () {
    User::factory()->unverified()->create(['email' => 'budi@unsika.ac.id']);

    $this->withSession(['pending_verification_email' => 'budi@unsika.ac.id'])
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Verifikasi Email Anda')
        ->assertSee('budi@unsika.ac.id')
        ->assertSee('Kirim Ulang Email')
        ->assertSee('Kembali Ke Beranda');
});

test('the waiting page shows a resend countdown while the cooldown is active', function () {
    User::factory()->unverified()->create([
        'email' => 'budi@unsika.ac.id',
        'verification_sent_at' => now(),
    ]);

    $this->withSession(['pending_verification_email' => 'budi@unsika.ac.id'])
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Kirim ulang dalam');
});

test('verified users skip the waiting page', function () {
    User::factory()->create(['email' => 'budi@unsika.ac.id']);

    $this->withSession(['pending_verification_email' => 'budi@unsika.ac.id'])
        ->get(route('verification.notice'))
        ->assertRedirect(route('login'));
});

test('resending sends a new activation email', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create(['email' => 'budi@unsika.ac.id']);

    $this->withSession(['pending_verification_email' => 'budi@unsika.ac.id'])
        ->post(route('verification.resend'))
        ->assertRedirect(route('verification.notice'));

    Notification::assertSentTo($user, VerifyEmail::class);
    expect($user->fresh()->verification_sent_at)->not->toBeNull();
});

test('resending within a minute does not send another activation email', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create([
        'email' => 'budi@unsika.ac.id',
        'verification_sent_at' => now(),
    ]);

    $this->withSession(['pending_verification_email' => 'budi@unsika.ac.id'])
        ->post(route('verification.resend'))
        ->assertRedirect(route('verification.notice'));

    Notification::assertNotSentTo($user, VerifyEmail::class);
});

test('resending shows cooldown instead of rate limit error', function () {
    Notification::fake();
    User::factory()->unverified()->create(['email' => 'budi@unsika.ac.id']);

    $this->withSession(['pending_verification_email' => 'budi@unsika.ac.id']);

    $this->post(route('verification.resend'))->assertRedirect(route('verification.notice'));
    $this->post(route('verification.resend'))->assertRedirect(route('verification.notice'));

    Notification::assertSentTo(User::first(), VerifyEmail::class, 1);
});

test('resending is ignored for an already verified account', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'budi@unsika.ac.id']);

    $this->withSession(['pending_verification_email' => 'budi@unsika.ac.id'])
        ->post(route('verification.resend'))
        ->assertRedirect(route('login'));

    Notification::assertNotSentTo($user, VerifyEmail::class);
});

test('a valid activation link verifies the email and logs the user in', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->getKey(),
        'hash' => $user->verificationHash(),
    ]);

    $this->get($url)
        ->assertRedirect(route('verification.success'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('an activation link is invalidated when a new verification email is sent', function () {
    $user = User::factory()->unverified()->create([
        'verification_sent_at' => now()->subSeconds(61),
    ]);

    $oldUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->getKey(),
        'hash' => $user->verificationHash(),
    ]);

    $this->withSession(['pending_verification_email' => $user->email])
        ->post(route('verification.resend'));

    $this->get($oldUrl)->assertRedirect(route('verification.expired'));
    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('an activation link with the wrong hash is rejected', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->getKey(),
        'hash' => sha1('lain@unsika.ac.id'),
    ]);

    $this->get($url)->assertRedirect(route('verification.expired'));

    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('an unsigned activation link is rejected', function () {
    $user = User::factory()->unverified()->create();

    $this->get(route('verification.verify', [
        'id' => $user->getKey(),
        'hash' => $user->verificationHash(),
    ]))->assertRedirect(route('verification.expired'));

    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('the success page shows the countdown and link to beranda', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('verification.success'))
        ->assertOk()
        ->assertSee('Email Terverifikasi')
        ->assertSee('Lanjut ke Beranda')
        ->assertSee('Detik');
});

test('the expired page shows the resend button and register link', function () {
    $this->get(route('verification.expired'))
        ->assertOk()
        ->assertSee('Link Verifikasi Kedaluwarsa')
        ->assertSee('Kirim Ulang Email')
        ->assertSee('Daftar Ulang');
});
