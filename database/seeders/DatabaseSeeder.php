<?php

namespace Database\Seeders;

use App\Services\ModuleInstallationSynchronizer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(ModuleInstallationSynchronizer::class)->synchronize();

        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call(LocalQaSeeder::class);
    }
}
