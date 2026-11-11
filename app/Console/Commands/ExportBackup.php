<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;

class ExportBackup extends Command
{
    protected $signature = 'admin:export-backup {backup : Nama file .enc dalam private/backups}';

    protected $description = 'Dekripsi backup ke private/backups/restore.sql; tidak mengubah database.';

    public function handle(): int
    {
        $name = $this->argument('backup');
        if (basename($name) !== $name || ! preg_match('/^workspace-[a-zA-Z0-9-]+\\.enc$/', $name)) {
            $this->error('Nama backup tidak valid.');

            return self::FAILURE;
        }
        $root = storage_path('app/private/backups');
        $sql = gzdecode(Crypt::decryptString(File::get($root.'/'.$name)));
        if (! is_string($sql)) {
            $this->error('Backup tidak valid.');

            return self::FAILURE;
        }
        File::put($root.'/restore.sql', $sql);
        @chmod($root.'/restore.sql', 0600);
        $this->info('SQL tersimpan di private/backups/restore.sql. Setelah restore database, jalankan chat:apply-deletions.');

        return self::SUCCESS;
    }
}
