<?php

namespace Tests\Feature;

use Database\Seeders\CoreDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_data_seeder_can_run_more_than_once(): void
    {
        $this->seed(CoreDataSeeder::class);
        $this->seed(CoreDataSeeder::class);

        $this->assertDatabaseCount('asset_models', 1);
        $this->assertDatabaseHas('asset_models', [
            'name' => 'Generic Laptop',
            'model_number' => 'GEN-LAP',
        ]);
    }
}
