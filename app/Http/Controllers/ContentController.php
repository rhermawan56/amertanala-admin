<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveContentRequest;
use App\Models\SiteContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    public static function site(): SiteContent
    {
        return SiteContent::firstOrCreate(['key' => 'landing'], ['draft' => config('workspace.default_content')]);
    }

    public function edit(): Response
    {
        return Inertia::render('workspace/Content', ['site' => self::site(), 'revisions' => DB::table('content_revisions')->latest('id')->limit(15)->get(['id', 'action', 'created_at'])->map(function ($revision) {
            $revision->created_at = Carbon::parse($revision->created_at)->toIso8601String();

            return $revision;
        })]);
    }

    public function save(SaveContentRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $site = self::site()->newQuery()->where('key', 'landing')->lockForUpdate()->firstOrFail();
            abort_if($site->version !== $request->integer('version'), 409, 'Konten telah berubah. Muat ulang sebelum menyimpan.');
            $site->update(['draft' => $request->validated('content'), 'version' => $site->version + 1]);
            $this->revision($site, $request, 'draft');
        });

        return back()->with('success', 'Draf tersimpan. Landing page belum berubah.');
    }

    public function publish(Request $request): RedirectResponse
    {
        $request->validate(['version' => ['required', 'integer']]);
        DB::transaction(function () use ($request) {
            $site = SiteContent::where('key', 'landing')->lockForUpdate()->firstOrFail();
            abort_if($site->version !== $request->integer('version'), 409, 'Draf telah berubah. Muat ulang dahulu.');
            $site->update(['published' => $site->draft, 'published_at' => now(), 'version' => $site->version + 1]);
            $this->revision($site, $request, 'published');
        });

        return back()->with('success', 'Konten diterbitkan. Landing page memperbarui cache paling lambat 30 detik.');
    }

    public function restore(Request $request, int $revision): RedirectResponse
    {
        $request->validate(['version' => ['required', 'integer']]);
        DB::transaction(function () use ($request, $revision) {
            $site = SiteContent::where('key', 'landing')->lockForUpdate()->firstOrFail();
            abort_if($site->version !== $request->integer('version'), 409);
            $old = DB::table('content_revisions')->where('site_content_id', $site->id)->find($revision);
            abort_unless($old, 404);
            $site->update(['draft' => json_decode($old->content, true), 'version' => $site->version + 1]);
            $this->revision($site, $request, 'restored');
        });

        return back()->with('success', 'Versi dipulihkan sebagai draf.');
    }

    public function published(): JsonResponse
    {
        $site = SiteContent::where('key', 'landing')->first();
        abort_unless($site?->published, 404);

        return response()->json(['content' => $site->published, 'version' => $site->version, 'published_at' => $site->published_at])->header('Cache-Control', 'public, max-age=30');
    }

    private function revision(SiteContent $site, Request $request, string $action): void
    {
        DB::table('content_revisions')->insert(['site_content_id' => $site->id, 'user_id' => $request->user()->id, 'content' => json_encode($site->draft), 'action' => $action, 'created_at' => now(), 'updated_at' => now()]);
    }
}
