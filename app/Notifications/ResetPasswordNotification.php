<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $menit = (int) config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Reset password akun '.config('upz.site.nama', 'UPZ BAZNAS PG'))
            ->greeting('Halo, '.($notifiable->name ?: 'Pengguna').'!')
            ->line('Kami menerima permintaan untuk mereset password akun Anda.')
            ->action('Buat password baru', $url)
            ->line('Link ini berlaku selama '.$menit.' menit.')
            ->line('Jika Anda tidak meminta reset password, abaikan email ini. Password Anda tidak akan berubah.');
    }
}
