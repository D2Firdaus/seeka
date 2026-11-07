<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('guests can view the login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Login SEEKA')
        ->assertSee('Masuk');
});

test('login requires an email and password', function () {
    $this->from('/login')->post('/login', [])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email', 'password']);

    expect(Auth::check())->toBeFalse();
});

test('login rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'budi@kampus.ac.id',
        'password' => 'secret-password',
    ]);

    $this->post('/login', [
        'email' => 'budi@kampus.ac.id',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    expect(Auth::check())->toBeFalse();
});

test('valid credentials authenticate the user', function () {
    User::factory()->create([
        'email' => 'budi@kampus.ac.id',
        'password' => 'secret-password',
    ]);

    $this->post('/login', [
        'email' => 'budi@kampus.ac.id',
        'password' => 'secret-password',
    ])->assertRedirect(route('home'));

    $this->assertAuthenticated();
});

test('authenticated users are redirected away from the login page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/login')
        ->assertRedirect(route('home'));
});

test('logging out ends the session and returns to the beranda', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});
