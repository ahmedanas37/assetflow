<?php

namespace Database\Factories\Domain\Accessories\Models;

use App\Domain\Accessories\Models\Accessory;
use App\Domain\Inventory\Enums\CategoryType;
use App\Domain\Inventory\Models\Category;
use App\Domain\Inventory\Models\Manufacturer;
use App\Domain\Locations\Models\Location;
use App\Domain\Vendors\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Accessories\Models\Accessory>
 */
class AccessoryFactory extends Factory
{
    protected $model = Accessory::class;

    public function definition(): array
    {
        $total = $this->faker->numberBetween(1, 50);

        return [
            'name' => ucfirst($this->faker->unique()->words(2, true)),
            'category_id' => Category::factory()->state([
                'type' => CategoryType::Accessory->value,
            ]),
            'manufacturer_id' => Manufacturer::factory(),
            'vendor_id' => Vendor::factory(),
            'location_id' => Location::factory(),
            'model_number' => $this->faker->optional()->bothify('ACC-###'),
            'quantity_total' => $total,
            'quantity_available' => $total,
            'reorder_threshold' => $this->faker->optional()->numberBetween(1, 10),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
