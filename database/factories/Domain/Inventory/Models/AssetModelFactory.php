<?php

namespace Database\Factories\Domain\Inventory\Models;

use App\Domain\Inventory\Models\AssetModel;
use App\Domain\Inventory\Models\Category;
use App\Domain\Inventory\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Inventory\Models\AssetModel>
 */
class AssetModelFactory extends Factory
{
    protected $model = AssetModel::class;

    public function definition(): array
    {
        return [
            'manufacturer_id' => Manufacturer::factory(),
            'category_id' => Category::factory(),
            'name' => ucfirst($this->faker->words(2, true)),
            'model_number' => strtoupper($this->faker->bothify('??-###')),
            'depreciation_months' => $this->faker->optional()->numberBetween(12, 60),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
