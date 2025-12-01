<?php

namespace Database\Factories;

use App\Models\BorrowItemInstance;
use App\Models\BorrowRequest;
use App\Models\Item;
use App\Models\ItemInstance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<\App\Models\BorrowItemInstance>
 */
class BorrowItemInstanceFactory extends Factory
{
    protected $model = BorrowItemInstance::class;

    public function definition(): array
    {
        $checkout = Carbon::now()->subDay();

        return [
            'borrow_request_id' => BorrowRequest::factory(),
            'item_id' => Item::factory(),
            'item_instance_id' => ItemInstance::factory(),
            'checked_out_at' => $checkout,
            'expected_return_at' => (clone $checkout)->addDays(3),
            'borrowed_qty' => 1,
            'return_condition' => 'pending',
        ];
    }

    public function returned(string $condition = 'good'): self
    {
        return $this->state(function (array $attributes) use ($condition) {
            $returnedAt = Carbon::now();

            return [
                'returned_at' => $returnedAt,
                'return_condition' => $condition,
                'condition_updated_at' => $returnedAt,
            ];
        });
    }
}
