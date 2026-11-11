<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email?} {--name=Administrator} {--bootstrap : Create the first local admin with a random password in private storage}';

    protected $description = 'Buat admin melalui terminal; tidak ada pendaftaran publik.';

    public function handle(): int
    {
        $email = mb_strtolower($this->argument('email') ?: $this->ask('Email admin'));
        $bootstrap = $this->option('bootstrap');
        if ($bootstrap && (! app()->environment('local') || User::where('is_admin', true)->exists())) {
            $this->error('Bootstrap hanya tersedia untuk admin lokal pertama.');

            return self::FAILURE;
        }
        $password = $bootstrap ? bin2hex(random_bytes(18)).'!Aa7' : $this->secret('Kata sandi (minimal 12 karakter)');
        $validator = Validator::make(['email' => $email, 'password' => $password], ['email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        $user = new User(['name' => $this->option('name'), 'email' => $email, 'password' => $password]);
        $user->is_admin = true;
        $user->email_verified_at = now();
        $user->save();
        if ($bootstrap) {
            Storage::disk('local')->put('initial-admin.txt', 'Email: '.$email.PHP_EOL.'Password: '.$password.PHP_EOL.'Masuk menggunakan email dan kata sandi. Hapus file ini setelah akses disimpan.');
        }
        $this->info('Admin dibuat. Masuk menggunakan email dan kata sandi.');

        return self::SUCCESS;
    }
}
