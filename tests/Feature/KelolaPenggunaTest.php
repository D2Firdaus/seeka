<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('guests are redirected to login when managing users', function () {
    $this->get(route('admin.users'))->assertRedirect(route('login'));
    $this->get(route('admin.users.create'))->assertRedirect(route('login'));
    $this->post(route('admin.users.store'), [])->assertRedirect(route('login'));
    $this->delete(route('admin.users.destroy', User::factory()->create()))->assertRedirect(route('login'));
});

test('regular users cannot manage users', function () {
    $user = User::factory()->create(['role' => 'user']);
    $other = User::factory()->create();

    $this->actingAs($user)->get(route('admin.users'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.users.edit', $other))->assertForbidden();
    $this->actingAs($user)->post(route('admin.users.store'), [])->assertForbidden();
    $this->actingAs($user)->patch(route('admin.users.update', $other), [])->assertForbidden();
    $this->actingAs($user)->delete(route('admin.users.destroy', $other))->assertForbidden();
});

test('admin can view the user list with roles', function () {
    $admin = User::factory()->create(['name' => 'Admin Utama', 'role' => 'admin']);
    User::factory()->create(['name' => 'Budi Santoso', 'role' => 'user']);
    User::factory()->create(['name' => 'Siti Aminah', 'role' => 'user']);

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertSee('Kelola Pengguna')
        ->assertSee('Admin Utama')
        ->assertSee('Budi Santoso')
        ->assertSee('Siti Aminah')
        ->assertSee('Admin')
        ->assertSee('Pengguna')
        ->assertSee('Tambah Pengguna');
});

test('admin can search users by name, email, or nim nip', function (string $column, string $value) {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['name' => 'Budi Santoso']);

    $target = User::factory()->create(['name' => 'Siti Aminah']);
    $target->forceFill([$column => $value])->save();

    $this->actingAs($admin)
        ->get(route('admin.users', ['q' => $value]))
        ->assertOk()
        ->assertSee('Siti Aminah')
        ->assertDontSee('Budi Santoso');
})->with([
    'name' => ['name', 'Siti Aminah'],
    'email' => ['email', 'siti@contoh.ac.id'],
    'nim_nip' => ['nim_nip', '1234567890'],
]);

test('admin can filter users by role', function () {
    $admin = User::factory()->create(['name' => 'Admin Utama', 'role' => 'admin']);
    User::factory()->create(['name' => 'Nia Kurnia', 'role' => 'admin']);
    User::factory()->create(['name' => 'Budi Santoso', 'role' => 'user']);

    $this->actingAs($admin)
        ->get(route('admin.users', ['role' => 'user']))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertDontSee('Nia Kurnia');
});

test('admin can create a user with the chosen role', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@contoh.ac.id',
            'nim_nip' => '1234567890',
            'phone' => '6281234567890',
            'role' => 'admin',
            'password' => 'kata-sandi',
            'password_confirmation' => 'kata-sandi',
        ])
        ->assertRedirect(route('admin.users'))
        ->assertSessionHas('success');

    $user = User::where('email', 'budi@contoh.ac.id')->firstOrFail();

    expect($user->name)->toBe('Budi Santoso')
        ->and($user->nim_nip)->toBe('1234567890')
        ->and($user->phone)->toBe('6281234567890')
        ->and($user->role)->toBe('admin')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('kata-sandi', $user->password))->toBeTrue();
});

test('user creation validates required fields and duplicates', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['email' => 'budi@contoh.ac.id', 'nim_nip' => '1234567890']);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => '',
            'email' => 'budi@contoh.ac.id',
            'nim_nip' => '1234567890',
            'role' => 'superuser',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ])
        ->assertSessionHasErrors(['name', 'email', 'nim_nip', 'role', 'password']);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'role', 'password']);
});

test('admin can update a user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['name' => 'Budi Santoso', 'role' => 'user']);

    $this->actingAs($admin)
        ->patch(route('admin.users.update', $user), [
            'name' => 'Budi Santoso Putra',
            'email' => 'budi.baru@contoh.ac.id',
            'nim_nip' => '9876543210',
            'phone' => '6289876543210',
            'role' => 'admin',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('admin.users'))
        ->assertSessionHas('success');

    $user->refresh();

    expect($user->name)->toBe('Budi Santoso Putra')
        ->and($user->email)->toBe('budi.baru@contoh.ac.id')
        ->and($user->nim_nip)->toBe('9876543210')
        ->and($user->phone)->toBe('6289876543210')
        ->and($user->role)->toBe('admin');
});

test('updating a user keeps the password when left blank', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('admin.users'));

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('admin cannot change their own role', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->from(route('admin.users.edit', $admin))
        ->patch(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'user',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertForbidden();

    expect($admin->fresh()->role)->toBe('admin');
});

test('admin can delete another user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect(route('admin.users'))
        ->assertSessionHas('success');

    expect($user->fresh()->trashed())->toBeTrue();
});

test('admin cannot delete their own account', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertForbidden();

    expect($admin->fresh()->trashed())->toBeFalse();
});

test('deleted users are no longer listed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['name' => 'Budi Santoso']);
    $user->delete();

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertDontSee('Budi Santoso');
});
