<?php

namespace App\Console\Commands;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

class SeedDemoCommand extends Command
{
    protected $signature = 'assetflow:seed-demo {--force : Run the demo seeder in production}';

    protected $description = 'Seed a comprehensive demo environment (users, inventory, assets, accessories, assignments, maintenance, and attachments).';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to seed demo data in production without --force.');

            return self::FAILURE;
        }

        $this->call('db:seed', [
            '--class' => DemoDataSeeder::class,
            '--force' => true,
        ]);

        $this->info('Demo data seeded.');

        return self::SUCCESS;
    }
}
