<?php

namespace Database\Factories;

use App\Models\ManpowerRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\ManpowerRequest>
 */
class ManpowerRequestFactory extends Factory
{
    protected $model = ManpowerRequest::class;

    public function definition(): array
    {
        $start = now()->addDays(3)->setTime(8, 0);
        $end = (clone $start)->addHours(8);

        return [
            'user_id' => User::factory(),
            'quantity' => 3,
            'role' => $this->faker->jobTitle(),
            'manpower_role_id' => null,
            'purpose' => $this->faker->sentence(6),
            'location' => $this->faker->streetAddress(),
            'municipality' => 'Tagoloan',
            'barangay' => 'Barangay Uno',
            'office_agency' => $this->faker->company(),
            'start_at' => $start,
            'end_at' => $end,
            'status' => 'pending',
            'public_token' => (string) Str::uuid(),
        ];
    }

    public function validated(): self
    {
        return $this->state(fn () => ['status' => 'validated']);
    }

    public function approved(int $approvedQuantity = 3): self
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'approved_quantity' => $approvedQuantity,
        ]);
    }
}
