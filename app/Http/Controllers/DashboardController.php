<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $site = ContentController::site();
        $days = collect(range(6, 0))->map(function (int $offset) {
            $day = now()->subDays($offset);

            return ['label' => $day->translatedFormat('D'), 'count' => Message::where('sender', 'guest')->whereDate('created_at', $day->toDateString())->count()];
        });

        return Inertia::render('workspace/Dashboard', [
            'stats' => [
                'themes' => count($site->draft['themes'] ?? []),
                'conversations' => Conversation::count(),
                'unread' => Conversation::whereHas('messages', fn ($query) => $query->where('sender', 'guest')->whereColumn('messages.id', '>', 'conversations.admin_read_id'))->count(),
                'published' => $site->published_at?->timezone('Asia/Bangkok')->format('d M Y, H:i'),
                'draft' => $site->published !== $site->draft,
            ],
            'activity' => DB::table('content_revisions')->leftJoin('users', 'users.id', '=', 'content_revisions.user_id')->latest('content_revisions.id')->limit(5)->get(['content_revisions.id', 'action', 'content_revisions.created_at', 'users.name']),
            'recent' => Conversation::latest('last_activity_at')->limit(4)->get(['id', 'name', 'theme', 'status', 'last_activity_at']),
            'chart' => $days,
            'landingUrl' => config('workspace.landing_url'),
        ]);
    }
}
