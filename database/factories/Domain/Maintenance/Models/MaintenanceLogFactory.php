<?php

namespace Database\Factories\Domain\Maintenance\Models;

use App\Domain\Assets\Models\Asset;
use App\Domain\Maintenance\Enums\MaintenanceStatus;
use App\Domain\Maintenance\Enums\MaintenanceType;
use App\Domain\Maintenance\Models\MaintenanceLog;
use App\Domain\Vendors\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Maintenance\Models\MaintenanceLog>
 */
class MaintenanceLogFactory extends Factory
{
    protected $model = MaintenanceLog::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', 'now');

        return [
            'asset_id' => Asset::factory(),
            'type' => $this->faker->randomElement(array_map(
                static fn (MaintenanceType $type) => $type->value,
                MaintenanceType::cases(),
            )),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $this->faker->optional()->dateTimeBetween($startDate, '+1 month')->format('Y-m-d'),
            'cost' => $this->faker->optional()->randomFloat(2, 25, 2500),
            'vendor_id' => Vendor::factory(),
            'notes' => $this->faker->optional()->sentence(),
            'performed_by' => $this->faker->optional()->name(),
            'status' => MaintenanceStatus::Open->value,
        ];
    }
}
