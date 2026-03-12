<?php

namespace Database\Factories\Domain\Assets\Models;

use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Locations\Models\Location;
use App\Domain\People\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Assets\Models\AssetAssignment>
 */
class AssetAssignmentFactory extends Factory
{
    protected $model = AssetAssignment::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(AssignmentType::cases());
        $assignedToId = match ($type) {
            AssignmentType::User => User::factory(),
            AssignmentType::Employee => Employee::factory(),
            AssignmentType::Location => Location::factory(),
        };

        return [
            'asset_id' => Asset::factory(),
            'assigned_to_type' => $type->value,
            'assigned_to_id' => $assignedToId,
            'assigned_to_label' => $this->faker->optional()->bothify('Desk-###'),
            'assigned_by_user_id' => User::factory(),
            'assigned_at' => now(),
            'due_at' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'returned_at' => null,
            'return_condition' => null,
            'notes' => $this->faker->optional()->sentence(),
            'location_at_assignment' => $this->faker->optional()->word(),
            'is_active' => true,
            'active_asset_id' => null,
        ];
    }
}
