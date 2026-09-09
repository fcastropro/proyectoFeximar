<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->string('email')->toString();

        if (! User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['No se encontró una cuenta registrada con este correo electrónico.'],
            ]);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with(
                'status',
                'Se ha enviado un enlace de recuperación a tu correo electrónico. Revisa tu bandeja de entrada.'
            );
        }

        if ($status === Password::RESET_THROTTLED) {
            throw ValidationException::withMessages([
                'email' => ['Has realizado demasiados intentos. Espera unos minutos antes de volver a intentarlo.'],
            ]);
        }

        // Nunca exponer claves internas passwords.*.
        throw ValidationException::withMessages([
            'email' => ['No se pudo enviar el enlace de recuperación. Intenta nuevamente más tarde.'],
        ]);
    }
}
