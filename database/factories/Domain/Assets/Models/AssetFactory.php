<?php

namespace Database\Factories\Domain\Assets\Models;

use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\StatusLabel;
use App\Domain\Inventory\Models\AssetModel;
use App\Domain\Inventory\Models\Category;
use App\Domain\Locations\Models\Location;
use App\Domain\Vendors\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Assets\Models\Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        $serial = $this->faker->boolean(70)
            ? $this->faker->unique()->bothify('SN-#####')
            : null;
        $warranty = $this->faker->optional()->dateTimeBetween('now', '+2 years');

        return [
            'asset_tag' => strtoupper($this->faker->unique()->bothify('AST-#####')),
            'serial' => $serial,
            'asset_model_id' => AssetModel::factory(),
            'category_id' => Category::factory(),
            'status_label_id' => StatusLabel::factory(),
            'location_id' => Location::factory(),
            'assigned_to_user_id' => null,
            'purchase_date' => $this->faker->optional()->date(),
            'purchase_cost' => $this->faker->optional()->randomFloat(2, 100, 5000),
            'vendor_id' => Vendor::factory(),
            'warranty_end_date' => $warranty?->format('Y-m-d'),
            'notes' => $this->faker->optional()->sentence(),
            'custom_fields' => [],
        ];
    }
}
