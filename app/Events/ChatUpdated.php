<?php

namespace App\Events;

use App\Models\ChatSession;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class ChatUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $conversationId) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('chat.'.$this->conversationId), new PrivateChannel('admin.inbox')];
        foreach (ChatSession::where('conversation_id', $this->conversationId)->where('expires_at', '>', now())->where('last_seen_at', '>', now()->subDay())->pluck('id') as $id) {
            $channels[] = new PrivateChannel('guest-session.'.$id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'chat.updated';
    }

    public function broadcastWith(): array
    {
        return ['conversation_id' => $this->conversationId];
    }
}
