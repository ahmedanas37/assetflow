<?php

namespace Database\Factories\Domain\Inventory\Models;

use App\Domain\Inventory\Enums\CategoryType;
use App\Domain\Inventory\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Inventory\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->word()),
            'type' => $this->faker->randomElement(array_map(
                static fn (CategoryType $type) => $type->value,
                CategoryType::cases(),
            )),
            'depreciation_months' => $this->faker->optional()->numberBetween(12, 60),
            'prefix' => strtoupper($this->faker->lexify('???')),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
