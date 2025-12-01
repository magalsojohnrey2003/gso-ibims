<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WalkInRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WalkInRequest>
 */
class WalkInRequestFactory extends Factory
{
    protected $model = WalkInRequest::class;

    public function definition(): array
    {
        return [
            'borrower_name' => $this->faker->name(),
            'office_agency' => $this->faker->company(),
            'contact_number' => $this->faker->numerify('09#########'),
            'address' => $this->faker->address(),
            'purpose' => $this->faker->sentence(6),
            'borrowed_at' => now()->subDay(),
            'returned_at' => null,
            'status' => 'pending',
            'created_by' => User::factory(),
        ];
    }

    public function approved(): self
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function delivered(): self
    {
        return $this->state(fn () => [
            'status' => 'delivered',
            'returned_at' => now()->addDays(2),
        ]);
    }
}
