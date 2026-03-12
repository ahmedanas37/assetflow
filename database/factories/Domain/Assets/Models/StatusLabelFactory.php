<?php

namespace Database\Factories\Domain\Assets\Models;

use App\Domain\Assets\Models\StatusLabel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Assets\Models\StatusLabel>
 */
class StatusLabelFactory extends Factory
{
    protected $model = StatusLabel::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->word()),
            'color' => $this->faker->optional()->hexColor(),
            'deployable' => $this->faker->boolean(70),
            'is_default' => false,
            'sort_order' => $this->faker->numberBetween(1, 10),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
