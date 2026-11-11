<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class ChatAccess
{
    public function session(Request $request): ?ChatSession
    {
        $token = $request->cookie(config('workspace.chat_cookie'));
        if (! is_string($token) || strlen($token) !== 64) {
            return null;
        }
        $session = ChatSession::where('token_hash', hash('sha256', $token))->first();
        if (! $session || $session->expires_at->isPast() || $session->last_seen_at->lt(now()->subDay())) {
            return null;
        }

        return $session;
    }

    public function conversation(Request $request, bool $touch = true): Conversation
    {
        $session = $this->session($request);
        abort_unless($session, 401, 'Sesi chat berakhir. Mulai chat baru atau pulihkan melalui email.');
        if ($touch) {
            $session->update(['last_seen_at' => now()]);
        }

        return Conversation::findOrFail($session->conversation_id);
    }

    public function issue(Conversation $conversation): Cookie
    {
        $token = bin2hex(random_bytes(32));
        ChatSession::create(['conversation_id' => $conversation->id, 'token_hash' => hash('sha256', $token), 'last_seen_at' => now(), 'expires_at' => now()->addDays(7)]);

        return cookie(config('workspace.chat_cookie'), $token, 10080, '/', null, (bool) config('session.secure'), true, false, 'strict');
    }
}
