<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('workspace/Media', ['media' => DB::table('media_assets')->latest('id')->paginate(24)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'], 'alt' => ['required', 'string', 'max:255']]);
        $file = $request->file('image');
        $path = $file->store('media', 'public');
        DB::table('media_assets')->insert(['path' => $path, 'name' => mb_substr(basename($file->getClientOriginalName()), 0, 255), 'alt' => $data['alt'], 'mime' => $file->getMimeType(), 'size' => $file->getSize(), 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Gambar diunggah. Salin URL untuk pratinjau tema.');
    }
}
