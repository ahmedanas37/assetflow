<?php

namespace Database\Factories\Domain\Accessories\Models;

use App\Domain\Accessories\Models\Accessory;
use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Locations\Models\Location;
use App\Domain\People\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Accessories\Models\AccessoryAssignment>
 */
class AccessoryAssignmentFactory extends Factory
{
    protected $model = AccessoryAssignment::class;

    public function definition(): array
    {
        $assignedToType = $this->faker->randomElement([
            AssignmentType::User->value,
            AssignmentType::Employee->value,
            AssignmentType::Location->value,
        ]);
        $assignedToId = match ($assignedToType) {
            AssignmentType::User->value => User::factory(),
            AssignmentType::Employee->value => Employee::factory(),
            AssignmentType::Location->value => Location::factory(),
        };

        return [
            'accessory_id' => Accessory::factory(),
            'assigned_to_type' => $assignedToType,
            'assigned_to_id' => $assignedToId,
            'assigned_to_label' => $this->faker->optional()->bothify('Desk-###'),
            'assigned_by_user_id' => User::factory(),
            'assigned_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'due_at' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'returned_at' => null,
            'quantity' => $this->faker->numberBetween(1, 5),
            'returned_quantity' => 0,
            'notes' => $this->faker->optional()->sentence(),
            'location_at_assignment' => $this->faker->optional()->city(),
            'is_active' => true,
        ];
    }
}
