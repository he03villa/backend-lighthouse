<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifica tu correo electrónico — Lighthouse')
            ->greeting('Hola '.$notifiable->name.'!')
            ->line('Gracias por registrarte en Lighthouse. Por favor verifica tu correo electrónico para comenzar.')
            ->action('Verificar correo', $verificationUrl)
            ->line('Si no creaste una cuenta, puedes ignorar este mensaje.');
    }

    protected function verificationUrl($notifiable): string
    {
        $id = $notifiable->getKey();
        $hash = sha1($notifiable->getEmailForVerification());

        return url("/api/v1/auth/verify-email/{$id}/{$hash}");
    }
}
