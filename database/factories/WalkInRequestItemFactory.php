<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\WalkInRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WalkInRequestItem>
 */
class WalkInRequestItemFactory extends Factory
{
    protected $model = WalkInRequestItem::class;

    public function definition(): array
    {
        return [
            'walk_in_request_id' => null,
            'item_id' => Item::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
        ];
    }
}
