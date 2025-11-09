<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ItemInstance;
use App\Models\Item;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ItemInstance>
 */
class ItemInstanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = $this->faker->numberBetween(2020, 2025);
        $categoryCode = str_pad($this->faker->numberBetween(1, 99), 2, '0', STR_PAD_LEFT);
        $gla = str_pad($this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);
        $serial = str_pad($this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $propertyNumber = "{$year}-{$categoryCode}-{$gla}-{$serial}";
        
        $statuses = ['available', 'borrowed', 'damaged', 'under_repair'];
        $status = $this->faker->randomElement($statuses);
        
        return [
            'property_number' => $propertyNumber,
            'year_procured' => $year,
            'category_code' => $categoryCode,
            'gla' => $gla,
            'serial' => $serial,
            'serial_int' => intval($serial),
            'serial_no' => $this->faker->optional(0.7)->regexify('[A-Z0-9]{1,4}'),
            'model_no' => $this->faker->optional(0.7)->regexify('[A-Z0-9]{3,8}'),
            'office_code' => str_pad($this->faker->numberBetween(1, 99), 2, '0', STR_PAD_LEFT),
            'status' => $status,
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Configure the factory for a specific item.
     */
    public function forItem(Item $item): static
    {
        return $this->state(function (array $attributes) use ($item) {
            return [
                'item_id' => $item->id,
            ];
        });
    }

    /**
     * Mark instance as available.
     */
    public function available(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'available',
            ];
        });
    }

    /**
     * Mark instance as borrowed.
     */
    public function borrowed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'borrowed',
            ];
        });
    }
}