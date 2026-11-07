<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('guests are redirected to login from the waiting page', function () {
    $this->get(route('verification.notice'))
        ->assertRedirect(route('login'));
});

test('the waiting page shows the registered email', function () {
    $user = User::factory()->unverified()->create(['email' => 'budi@unsika.ac.id']);

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Verifikasi Email Anda')
        ->assertSee('budi@unsika.ac.id')
        ->assertSee('Kirim Ulang Email')
        ->assertSee('Kembali Ke Beranda');
});

test('the waiting page shows a resend countdown while the cooldown is active', function () {
    $user = User::factory()->unverified()->create(['verification_sent_at' => now()]);

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Kirim ulang dalam');
});

test('unverified users are redirected to the waiting page from the beranda', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('home'))
        ->assertRedirect(route('verification.notice'));
});

test('verified users skip the waiting page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('verification.notice'))
        ->assertRedirect(route('home'));
});

test('resending sends a new activation email', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.resend'))
        ->assertRedirect(route('verification.notice'));

    Notification::assertSentTo($user, VerifyEmail::class);
    expect($user->fresh()->verification_sent_at)->not->toBeNull();
});

test('resending within a minute does not send another activation email', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create(['verification_sent_at' => now()]);

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.resend'))
        ->assertRedirect(route('verification.notice'));

    Notification::assertNotSentTo($user, VerifyEmail::class);
});

test('resending the activation email is rate limited', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    $this->post(route('verification.resend'))->assertRedirect();
    $this->post(route('verification.resend'))->assertStatus(429);
});

test('resending is ignored for an already verified account', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.resend'))
        ->assertRedirect(route('home'));

    Notification::assertNotSentTo($user, VerifyEmail::class);
});

test('an unverified user can log out from the waiting page', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

test('a valid activation link verifies the email', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->getKey(),
        'hash' => $user->verificationHash(),
    ]);

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect(route('verification.success'));

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

    $this->actingAs($user)->post(route('verification.resend'));

    $this->actingAs($user)->get($oldUrl)->assertForbidden();
    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('an activation link with the wrong hash is rejected', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->getKey(),
        'hash' => sha1('lain@unsika.ac.id'),
    ]);

    $this->actingAs($user)
        ->get($url)
        ->assertForbidden();

    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('an activation link cannot be used by another account', function () {
    $owner = User::factory()->unverified()->create();
    $other = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $owner->getKey(),
        'hash' => $owner->verificationHash(),
    ]);

    $this->actingAs($other)
        ->get($url)
        ->assertForbidden();

    expect($owner->fresh()->email_verified_at)->toBeNull();
});

test('an unsigned activation link is rejected', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.verify', [
            'id' => $user->getKey(),
            'hash' => $user->verificationHash(),
        ]))
        ->assertForbidden();

    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('the activation link requires login', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->getKey(),
        'hash' => $user->verificationHash(),
    ]);

    $this->get($url)->assertRedirect(route('login'));

    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('the success page greets a verified user', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('verification.success'))
        ->assertOk()
        ->assertSee('Email Terverifikasi')
        ->assertSee('Lanjut ke Beranda');
});

test('the success page is hidden from unverified users', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('verification.success'))
        ->assertRedirect(route('verification.notice'));
});
