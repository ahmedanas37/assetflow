<?php

namespace Database\Factories\Domain\Inventory\Models;

use App\Domain\Inventory\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Inventory\Models\Manufacturer>
 */
class ManufacturerFactory extends Factory
{
    protected $model = Manufacturer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
