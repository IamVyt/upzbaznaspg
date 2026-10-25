<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);
        $menit = (int) config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Verifikasi email akun '.config('upz.site.nama', 'UPZ BAZNAS PG'))
            ->greeting('Halo, '.($notifiable->name ?: 'Sahabat').'!')
            ->line('Terima kasih telah mendaftar di layanan UPZ BAZNAS Petrokimia Gresik.')
            ->line('Klik tombol di bawah untuk memverifikasi alamat email Anda.')
            ->action('Verifikasi email', $url)
            ->line('Link ini berlaku selama '.$menit.' menit.')
            ->line('Jika Anda tidak membuat akun, abaikan email ini.');
    }
}
