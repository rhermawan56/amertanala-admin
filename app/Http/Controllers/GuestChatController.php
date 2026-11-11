<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\SiteContent;
use App\Services\ChatAccess;
use App\Services\ChatMessages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class GuestChatController extends Controller
{
    public function page(): Response
    {
        return Inertia::render('workspace/GuestChat', ['parentOrigins' => config('workspace.frame_origins')]);
    }

    public function current(Request $request, ChatAccess $access): JsonResponse
    {
        $session = $access->session($request);

        return response()->json(['conversation' => $session ? Conversation::find($session->conversation_id) : null, 'channel' => $session ? 'guest-session.'.$session->id : null]);
    }

    public function start(Request $request, ChatAccess $access, ChatMessages $messages): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'theme_id' => ['nullable', 'string', 'max:100'], 'body' => ['required', 'string', 'max:2000'], 'client_id' => ['required', 'uuid'], 'website' => ['nullable', 'max:0']]);
        if ($access->session($request)) {
            $conversation = $access->conversation($request);
            $message = $messages->send($conversation, 'guest', $data);

            return response()->json(['conversation' => $conversation, 'message' => $message]);
        }
        $browser = $request->session()->getId();
        $startKey = hash('sha256', $browser.'|'.$data['client_id']);
        $previous = Conversation::where('start_key', $startKey)->first();
        if ($previous) {
            return response()->json(['conversation' => $previous, 'message' => $messages->send($previous, 'guest', $data)])->withCookie($access->issue($previous));
        }
        abort_if(RateLimiter::tooManyAttempts('chat-create:'.$browser, 3), 429, 'Terlalu banyak percakapan. Coba satu jam lagi.');
        $site = SiteContent::where('key', 'landing')->first();
        $content = $site?->published ?? config('workspace.default_content');
        abort_unless($content['chat_enabled'] ?? true, 503, 'Chat sedang tidak tersedia.');
        $theme = collect($content['themes'] ?? [])->firstWhere('id', $data['theme_id'] ?? '');
        [$conversation, $cookie, $message] = DB::transaction(function () use ($data, $theme, $access, $messages, $startKey) {
            $conversation = Conversation::firstOrCreate(['start_key' => $startKey], ['name' => $data['name'], 'theme' => $theme['name'] ?? null, 'last_activity_at' => now()]);

            return [$conversation, $access->issue($conversation), $messages->send($conversation, 'guest', $data)];
        });
        RateLimiter::hit('chat-create:'.$browser, 3600);

        return response()->json(['conversation' => $conversation, 'message' => $message], 201)->withCookie($cookie);
    }

    public function messages(Request $request, ChatAccess $access): JsonResponse
    {
        $conversation = $access->conversation($request, false);
        $after = max(0, $request->integer('after'));

        return response()->json(['messages' => $conversation->messages()->where('id', '>', $after)->orderBy('id')->limit(100)->get(), 'conversation' => $conversation]);
    }

    public function send(Request $request, ChatAccess $access, ChatMessages $messages): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000'], 'client_id' => ['required', 'uuid']]);
        $conversation = $access->conversation($request);
        $key = 'chat-message:'.$conversation->id;
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429, 'Batas 10 pesan per menit tercapai.');
        RateLimiter::hit($key);

        return response()->json(['message' => $messages->send($conversation, 'guest', $data)]);
    }

    public function broadcastAuth(Request $request, ChatAccess $access): JsonResponse
    {
        $conversation = $access->conversation($request, false);
        abort_unless($request->string('channel_name')->toString() === 'private-guest-session.'.$access->session($request)->id, 403);
        $request->validate(['socket_id' => ['required', 'regex:/^\\d+\\.\\d+$/']]);
        $pusher = Broadcast::connection('reverb')->getPusher();

        return response()->json(json_decode($pusher->authorizeChannel($request->string('channel_name'), $request->string('socket_id')), true));
    }
}
