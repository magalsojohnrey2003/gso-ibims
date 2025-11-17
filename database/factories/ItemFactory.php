<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Item;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Office Supplies',
            'Electronics', 
            'Furniture',
            'Computer Equipment',
            'Audio Visual',
            'Tools & Equipment',
            'Vehicles',
            'Security Equipment',
            'Medical Equipment',
            'Sports Equipment'
        ];

        $itemNames = [
            'Office Chair',
            'Desktop Computer', 
            'Laptop Computer',
            'Printer',
            'Scanner',
            'Office Desk',
            'Filing Cabinet',
            'Projector',
            'Whiteboard',
            'Conference Table',
            'Telephone',
            'Shredder',
            'Air Conditioner',
            'Electric Fan',
            'Water Dispenser',
            'Photocopier',
            'Binding Machine',
            'Laminating Machine',
            'Paper Cutter',
            'Stapler',
            'Hole Puncher',
            'Calculator',
            'Monitor',
            'Keyboard',
            'Mouse',
            'UPS',
            'Router',
            'Switch',
            'Camera',
            'Speaker System',
            'Microphone',
            'Extension Cord',
            'Surge Protector',
            'Flashlight',
            'Fire Extinguisher',
            'First Aid Kit',
            'Tool Box',
            'Drill',
            'Hammer',
            'Screwdriver Set'
        ];

        $totalQty = $this->faker->numberBetween(1, 50);
        $availableQty = $totalQty; // All items available, none borrowed

        return [
            'name' => $this->faker->randomElement($itemNames),
            'category' => $this->faker->randomElement($categories),
            'total_qty' => $totalQty,
            'available_qty' => $availableQty,
            'photo' => 'images/item.png', // Default photo
            'acquisition_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'acquisition_cost' => $this->faker->numberBetween(1000, 100000), // PHP 10.00 to PHP 1,000.00 (in centavos)
        ];
    }
}