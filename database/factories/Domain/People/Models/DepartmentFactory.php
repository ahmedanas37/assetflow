<?php

namespace Database\Factories\Domain\People\Models;

use App\Domain\People\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\People\Models\Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->word()).' Department',
            'manager_user_id' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
