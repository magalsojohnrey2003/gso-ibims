<?php

namespace Database\Factories;

use App\Models\ManpowerRequest;
use App\Models\ManpowerRequestRole;
use App\Models\ManpowerRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ManpowerRequestRole>
 */
class ManpowerRequestRoleFactory extends Factory
{
    protected $model = ManpowerRequestRole::class;

    public function definition(): array
    {
        return [
            'manpower_request_id' => ManpowerRequest::factory(),
            'manpower_role_id' => ManpowerRole::factory(),
            'role_name' => $this->faker->jobTitle(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'approved_quantity' => null,
        ];
    }
}
