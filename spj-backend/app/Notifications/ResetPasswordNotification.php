<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // URL frontend (Vite), bukan URL API
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        $resetUrl = $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Reset Password — Sistem SPJ')
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Kami menerima permintaan untuk mereset password akun Sistem SPJ Anda.')
            ->line('Klik tombol di bawah untuk melanjutkan. Link ini berlaku **60 menit**.')
            ->action('Reset Password', $resetUrl)
            ->line('Jika Anda tidak meminta reset password, abaikan email ini — password Anda tetap aman.')
            ->salutation('Salam, Tim Sistem SPJ');
    }
}