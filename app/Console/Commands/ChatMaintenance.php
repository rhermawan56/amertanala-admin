<?php

namespace App\Console\Commands;

use App\Models\ChatSession;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\UnreadChat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ChatMaintenance extends Command
{
    protected $signature = 'chat:maintenance';

    protected $description = 'Hapus chat kedaluwarsa dan kirim notifikasi tanpa isi pesan.';

    public function handle(): int
    {
        ChatSession::where('expires_at', '<', now())->orWhere('last_seen_at', '<', now()->subDay())->delete();
        DB::table('chat_access_links')->where('expires_at', '<', now())->delete();
        Conversation::where('last_activity_at', '<', now()->subDays(90))->select('id')->chunkById(500, function ($conversations) {
            $ids = $conversations->pluck('id')->all();
            Storage::disk('local')->append('chat-deletions.txt', implode(PHP_EOL, $ids));
            Conversation::whereIn('id', $ids)->delete();
        });
        Conversation::whereHas('messages', fn ($q) => $q->where('sender', 'guest')->whereColumn('messages.id', '>', 'conversations.admin_read_id')->where('created_at', '<', now()->subMinutes(5)))
            ->where(fn ($q) => $q->whereNull('last_notified_at')->orWhere('last_notified_at', '<', now()->subMinutes(15)))
            ->each(function (Conversation $conversation) {
                Notification::send(User::where('is_admin', true)->get(), new UnreadChat($conversation->id));
                $conversation->update(['last_notified_at' => now()]);
            });

        return self::SUCCESS;
    }
}
