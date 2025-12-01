<?php

namespace Database\Factories;

use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\BorrowRequestItem>
 */
class BorrowRequestItemFactory extends Factory
{
    protected $model = BorrowRequestItem::class;

    public function definition(): array
    {
        return [
            'borrow_request_id' => BorrowRequest::factory(),
            'item_id' => Item::factory(),
            'quantity' => $this->faker->numberBetween(1, 3),
            'is_manpower' => false,
        ];
    }

    public function manpower(Item $manpowerPlaceholder = null, int $quantity = 1): self
    {
        return $this->state(function (array $attributes) use ($manpowerPlaceholder, $quantity) {
            $itemId = $manpowerPlaceholder?->id ?? $attributes['item_id'] ?? Item::factory();

            return [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'is_manpower' => true,
            ];
        });
    }
}
