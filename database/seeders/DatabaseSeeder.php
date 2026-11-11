<?php

namespace Database\Seeders;

use App\Http\Controllers\ContentController;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        ContentController::site();
    }
}
