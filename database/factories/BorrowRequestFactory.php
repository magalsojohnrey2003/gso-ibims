<?php

namespace Database\Factories;

use App\Models\BorrowRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<\App\Models\BorrowRequest>
 */
class BorrowRequestFactory extends Factory
{
    protected $model = BorrowRequest::class;

    public function definition(): array
    {
        $borrowDate = Carbon::now()->addDays(2)->startOfDay();
        $returnDate = (clone $borrowDate)->addDays(2);

        return [
            'user_id' => User::factory(),
            'borrow_date' => $borrowDate,
            'return_date' => $returnDate,
            'time_of_usage' => '08:00 - 17:00',
            'purpose_office' => $this->faker->company(),
            'purpose' => $this->faker->sentence(6),
            'location' => $this->faker->streetAddress(),
            'status' => 'pending',
            'delivery_status' => null,
        ];
    }

    public function validated(): self
    {
        return $this->state(fn () => ['status' => 'validated']);
    }

    public function approved(): self
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function dispatched(): self
    {
        return $this->state(function () {
            $timestamp = Carbon::now();

            return [
                'status' => 'approved',
                'delivery_status' => 'dispatched',
                'dispatched_at' => $timestamp,
            ];
        });
    }

    public function delivered(): self
    {
        return $this->state(function () {
            $timestamp = Carbon::now();

            return [
                'status' => 'approved',
                'delivery_status' => 'delivered',
                'dispatched_at' => $timestamp->copy()->subHour(),
                'delivered_at' => $timestamp,
            ];
        });
    }
}
