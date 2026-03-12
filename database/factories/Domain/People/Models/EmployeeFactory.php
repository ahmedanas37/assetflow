<?php

namespace Database\Factories\Domain\People\Models;

use App\Domain\People\Enums\UserStatus;
use App\Domain\People\Models\Department;
use App\Domain\People\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\People\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'employee_id' => $this->faker->optional()->bothify('EMP-####'),
            'name' => $this->faker->name(),
            'email' => $this->faker->optional()->safeEmail(),
            'department_id' => Department::factory(),
            'title' => $this->faker->optional()->jobTitle(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'status' => $this->faker->randomElement([UserStatus::Active->value, UserStatus::Inactive->value]),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
