<?php

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('Auth/ForgotPassword'));
});

test('login screen shows forgot password link when route exists', function () {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->where('canResetPassword', true));
});

test('existing email sends reset notification and shows success status', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->from('/forgot-password')
        ->post('/forgot-password', ['email' => $user->email])
        ->assertRedirect('/forgot-password')
        ->assertSessionHas(
            'status',
            'Se ha enviado un enlace de recuperación a tu correo electrónico. Revisa tu bandeja de entrada.'
        )
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('unknown email shows not found message and does not send notification', function () {
    Notification::fake();

    $this->from('/forgot-password')
        ->post('/forgot-password', ['email' => 'noexiste@feximar.test'])
        ->assertRedirect('/forgot-password')
        ->assertSessionHasErrors([
            'email' => 'No se encontró una cuenta registrada con este correo electrónico.',
        ]);

    Notification::assertNothingSent();
});

test('reset password notification uses feximar subject', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $mail = $notification->toMail($user);

        return $mail->subject === 'Recuperación de contraseña - FEXIMAR';
    });
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/ResetPassword'));

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'))
            ->assertSessionHas(
                'status',
                'Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión.'
            );

        expect(Hash::check('NuevaClave123!', $user->fresh()->password))->toBeTrue();

        return true;
    });
});

test('invalid reset token is rejected', function () {
    $user = User::factory()->create();

    $this->from('/reset-password/token-invalido')
        ->post('/reset-password', [
            'token' => 'token-invalido',
            'email' => $user->email,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ])
        ->assertSessionHasErrors('email')
        ->assertSessionMissing('status');

    expect(Hash::check('NuevaClave123!', $user->fresh()->password))->toBeFalse();
});

test('password reset responses never expose raw translation keys', function () {
    Notification::fake();

    $response = $this->from('/forgot-password')
        ->post('/forgot-password', ['email' => 'otro-inexistente@feximar.test']);

    $response->assertSessionHasErrors('email');

    $bag = session('errors');
    $messages = is_array($bag)
        ? collect($bag)->flatten()->implode(' ')
        : implode(' ', $bag->all());

    expect($messages)->not->toContain('passwords.');
});
