<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ChatMessages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    public function index(Request $request): Response
    {
        $search = mb_substr($request->string('q')->toString(), 0, 100);
        $conversations = Conversation::query()->when($search, fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('theme', 'like', '%'.$search.'%')))
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender', 'guest')->whereColumn('messages.id', '>', 'conversations.admin_read_id')])
            ->latest('last_activity_at')->paginate(30)->withQueryString();
        $active = $request->filled('conversation') ? Conversation::findOrFail($request->string('conversation')) : $conversations->first();

        return Inertia::render('workspace/Inbox', [
            'conversations' => $conversations,
            'active' => $active,
            'messages' => $active?->messages()->orderByDesc('id')->limit(100)->get()->reverse()->values() ?? [],
            'query' => $search,
        ]);
    }

    public function messages(Conversation $conversation, Request $request): JsonResponse
    {
        $query = $conversation->messages();
        if ($request->filled('before')) {
            return response()->json(['messages' => $query->where('id', '<', $request->integer('before'))->latest('id')->limit(100)->get()->reverse()->values()]);
        }

        return response()->json(['messages' => $query->where('id', '>', max(0, $request->integer('after')))->orderBy('id')->limit(100)->get()]);
    }

    public function read(Conversation $conversation, Request $request): JsonResponse
    {
        $data = $request->validate(['id' => ['required', 'integer', 'min:0']]);
        $max = $conversation->messages()->max('id') ?? 0;
        $conversation->update(['admin_read_id' => max($conversation->admin_read_id, min($max, $data['id']))]);

        return response()->json(['ok' => true]);
    }

    public function send(Conversation $conversation, Request $request, ChatMessages $messages): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000'], 'client_id' => ['required', 'uuid']]);

        return response()->json(['message' => $messages->send($conversation, 'admin', $data)]);
    }

    public function close(Conversation $conversation): RedirectResponse
    {
        $conversation->update(['status' => 'closed']);

        return back()->with('success', 'Percakapan ditutup. Pesan baru akan membukanya kembali.');
    }
}
