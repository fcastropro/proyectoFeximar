<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends ResetPasswordNotification
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Recuperación de contraseña - FEXIMAR')
            ->greeting('Hola')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta en FEXIMAR, Sistema Inteligente para Gestión y Análisis de Exportaciones Florícolas.')
            ->action('Restablecer contraseña', $url)
            ->line("Este enlace de recuperación expirará en {$expire} minutos.")
            ->line('Si no solicitaste restablecer tu contraseña, puedes ignorar este mensaje.')
            ->salutation('Atentamente, Equipo FEXIMAR');
    }
}
