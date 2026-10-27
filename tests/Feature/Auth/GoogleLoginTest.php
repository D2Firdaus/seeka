<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

test('login page offers google sign in', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Masuk dengan Google');
});

test('guests are sent to google to sign in', function () {
    Socialite::fake('google');

    $this->get(route('google.login'))->assertRedirect();
});

test('google callback creates an account and signs the user in', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-uid-1',
        'name' => 'Budi Santoso',
        'email' => 'budi@kampus.ac.id',
    ]));

    $this->get(route('google.callback'))->assertRedirect(route('home'));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'budi@kampus.ac.id',
        'google_id' => 'google-uid-1',
    ]);
});

test('google callback links an existing account with the same email', function () {
    $user = User::factory()->create(['email' => 'budi@kampus.ac.id']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-uid-2',
        'name' => 'Budi Santoso',
        'email' => 'budi@kampus.ac.id',
    ]));

    $this->get(route('google.callback'))->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->google_id)->toBe('google-uid-2');
});

test('google callback signs in the account that already has the google id', function () {
    $user = User::factory()->create([
        'email' => 'budi@kampus.ac.id',
        'google_id' => 'google-uid-3',
    ]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-uid-3',
        'name' => 'Budi Santoso',
        'email' => 'lain@kampus.ac.id',
    ]));

    $this->get(route('google.callback'))->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

test('google callback refuses a soft deleted account', function () {
    User::factory()->create(['email' => 'budi@kampus.ac.id'])->delete();

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-uid-4',
        'name' => 'Budi Santoso',
        'email' => 'budi@kampus.ac.id',
    ]));

    $this->get(route('google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});
