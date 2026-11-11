<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplyChatDeletions extends Command
{
    protected $signature = 'chat:apply-deletions';

    protected $description = 'Terapkan kembali penghapusan chat setelah restore backup.';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $ids = $disk->exists('chat-deletions.txt') ? explode("\n", $disk->get('chat-deletions.txt')) : [];
        foreach (array_chunk(array_unique(array_filter($ids, fn ($id) => Str::isUuid(trim($id)))), 500) as $batch) {
            Conversation::whereIn('id', array_map('trim', $batch))->delete();
        }
        $this->info('Daftar penghapusan diterapkan.');

        return self::SUCCESS;
    }
}
