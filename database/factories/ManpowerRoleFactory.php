<?php

namespace Database\Factories;

use App\Models\ManpowerRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ManpowerRole>
 */
class ManpowerRoleFactory extends Factory
{
    protected $model = ManpowerRole::class;

    public function definition(): array
    {
        return [
            'name' => sprintf('Role %s', $this->faker->unique()->jobTitle()),
        ];
    }
}
