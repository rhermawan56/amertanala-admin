<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UnreadChat extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $conversationId) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Pesan chat belum dibaca')->line('Ada percakapan yang menunggu balasan di Amertanala.')->action('Buka inbox', url('/inbox?conversation='.$this->conversationId));
    }
}
