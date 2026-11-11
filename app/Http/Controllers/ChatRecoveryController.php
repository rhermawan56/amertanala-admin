<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\Conversation;
use App\Notifications\ChatAccessLink;
use App\Services\ChatAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class ChatRecoveryController extends Controller
{
    public function request(Request $request, ChatAccess $access): JsonResponse
    {
        abort_if(in_array(config('mail.default'), ['array', 'log'], true), 503, 'Pemulihan email belum tersedia. Pertahankan cookie chat Anda.');
        $data = $request->validate(['email' => ['required', 'email', 'max:255'], 'purpose' => ['required', 'in:verify,recover']]);
        $email = mb_strtolower($data['email']);
        $key = 'chat-recovery:'.hash('sha256', $email);
        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 3600);
            $conversation = $data['purpose'] === 'verify' ? $access->conversation($request) : Conversation::where('email', $email)->whereNotNull('email_verified_at')->latest('last_activity_at')->first();
            if ($conversation) {
                $token = bin2hex(random_bytes(32));
                DB::table('chat_access_links')->insert(['conversation_id' => $conversation->id, 'token_hash' => hash('sha256', $token), 'purpose' => $data['purpose'], 'email' => $email, 'expires_at' => now()->addMinutes(15), 'created_at' => now(), 'updated_at' => now()]);
                Notification::route('mail', $email)->notify(new ChatAccessLink(url('/chat/access/'.$token), $data['purpose']));
            }
        }

        return response()->json(['message' => 'Jika permintaan memenuhi syarat, tautan akan dikirim. Periksa email Anda.']);
    }

    public function show(string $token): Response
    {
        abort_unless(preg_match('/^[a-f0-9]{64}$/', $token), 404);
        $link = DB::table('chat_access_links')->where('token_hash', hash('sha256', $token))->where('expires_at', '>', now())->first();

        return Inertia::render('workspace/Recover', ['valid' => (bool) $link, 'token' => $token]);
    }

    public function confirm(string $token, Request $request, ChatAccess $access): RedirectResponse
    {
        abort_unless(preg_match('/^[a-f0-9]{64}$/', $token), 404);
        $cookie = DB::transaction(function () use ($token, $request, $access) {
            $link = DB::table('chat_access_links')->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
            abort_unless($link && now()->lt($link->expires_at), 410, 'Tautan sudah digunakan atau kedaluwarsa.');
            $conversation = Conversation::whereKey($link->conversation_id)->lockForUpdate()->firstOrFail();
            if ($link->purpose === 'verify') {
                abort_unless($access->session($request)?->conversation_id === $conversation->id, 403, 'Konfirmasi email dari browser asal percakapan.');
                $conversation->update(['email' => $link->email, 'email_verified_at' => now()]);
            }
            ChatSession::where('conversation_id', $conversation->id)->delete();
            DB::table('chat_access_links')->where('conversation_id', $conversation->id)->delete();

            return $access->issue($conversation);
        });

        return redirect('/chat')->withCookie($cookie);
    }
}
