<?php

namespace App\Services;

use App\Events\ChatUpdated;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class ChatMessages
{
    public function send(Conversation $conversation, string $sender, array $data): Message
    {
        return DB::transaction(function () use ($conversation, $sender, $data) {
            $locked = Conversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            $message = Message::firstOrCreate(
                ['conversation_id' => $locked->id, 'sender' => $sender, 'client_id' => $data['client_id']],
                ['body' => $data['body']]
            );
            if ($message->wasRecentlyCreated) {
                $locked->update(['last_activity_at' => now(), 'status' => 'open']);
                ChatUpdated::dispatch($locked->id);
            }

            return $message;
        });
    }
}
