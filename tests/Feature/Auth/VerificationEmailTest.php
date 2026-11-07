<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the activation email renders the html and text versions with a logo URL', function () {
    config(['mail.logo_url' => null]);

    $user = User::factory()->unverified()->create([
        'name' => 'Budi Santoso',
        'email' => 'budi@unsika.ac.id',
    ]);

    $mail = (new VerifyEmail)->toMail($user);

    expect($mail->subject)->toBe('Aktivasi Akun '.config('app.name'))
        ->and($mail->view)->toMatchArray([
            'html' => 'emails.auth.verify',
            'text' => 'emails.auth.verify-text',
        ]);

    $url = $mail->data()['url'];
    $html = html_entity_decode($mail->render());
    $text = view($mail->view['text'], $mail->data())->render();

    expect($html)
        ->toContain('Halo, Budi Santoso.')
        ->toContain('budi@unsika.ac.id')
        ->toContain('Aktifkan Akun')
        ->toContain('background-color: #921300')
        ->toContain(asset('images/seeka-mark.png'))
        ->not->toContain('data:image/png;base64')
        ->toContain('Link ini berlaku 60 menit')
        ->toContain($url);

    expect($text)
        ->toContain('Halo, Budi Santoso.')
        ->toContain('budi@unsika.ac.id')
        ->toContain('Link ini berlaku 60 menit')
        ->toContain($url);
});

test('the activation email uses the configured public logo URL', function () {
    config(['mail.logo_url' => 'https://cdn.example.com/seeka-mark.png']);

    $user = User::factory()->unverified()->create();

    $html = html_entity_decode((new VerifyEmail)->toMail($user)->render());

    expect($html)
        ->toContain('https://cdn.example.com/seeka-mark.png')
        ->not->toContain('data:image/png;base64');
});
