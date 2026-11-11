<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChatAccessLink extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(public string $url, public string $purpose) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Akses chat Amertanala')
            ->line($this->purpose === 'verify' ? 'Konfirmasikan email untuk pemulihan chat Anda.' : 'Pulihkan akses percakapan Amertanala Anda.')
            ->line('Tautan berlaku 15 menit. Membuka tautan belum menggunakannya.')
            ->action('Konfirmasi akses', $this->url)->line('Abaikan jika Anda tidak meminta akses.');
    }
}
