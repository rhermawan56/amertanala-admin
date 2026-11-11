<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class BackupWorkspace extends Command
{
    protected $signature = 'admin:backup';

    protected $description = 'Backup MySQL terenkripsi (batas lokal 16 MB), retensi 30 hari. Simpan APP_KEY terpisah.';

    public function handle(): int
    {
        $directory = storage_path('app/private/backups');
        File::ensureDirectoryExists($directory);
        $credentials = tempnam($directory, 'client-');
        $escape = fn (string $value): string => str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], $value);
        $database = config('database.connections.mysql');
        File::put($credentials, "[client]\nuser=\"".$escape($database['username'])."\"\npassword=\"".$escape($database['password'])."\"\n");
        @chmod($credentials, 0600);
        try {
            $process = new Process([config('workspace.mysqldump'), '--defaults-extra-file='.$credentials, '--host='.$database['host'], '--port='.$database['port'], '--single-transaction', '--no-tablespaces', '--skip-lock-tables', '--skip-triggers', $database['database']]);
            $process->setTimeout(60);
            $sql = '';
            $process->run(function (string $type, string $buffer) use (&$sql): void {
                if ($type === Process::OUT) {
                    if (strlen($sql) + strlen($buffer) > 16 * 1024 * 1024) {
                        throw new \RuntimeException('Ukuran backup melebihi batas lokal 16 MB.');
                    }
                    $sql .= $buffer;
                }
            });
            if (! $process->isSuccessful()) {
                $this->error('Backup gagal. Periksa konfigurasi mysqldump dan akses database.');

                return self::FAILURE;
            }
            $file = $directory.'/workspace-'.now()->format('Ymd-His').'-'.Str::random(8).'.enc';
            File::put($file, Crypt::encryptString(gzencode($sql, 6)));
            @chmod($file, 0600);
            foreach (File::files($directory) as $existing) {
                if (str_starts_with($existing->getFilename(), 'workspace-') && $existing->getExtension() === 'enc' && $existing->getMTime() < now()->subDays(30)->timestamp) {
                    File::delete($existing->getPathname());
                }
            }
            $this->info('Backup terenkripsi tersimpan: '.$file);

            return self::SUCCESS;
        } finally {
            File::delete($credentials);
        }
    }
}
