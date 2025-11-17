<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\ItemInstance;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 50 items for testing table scrolling and header functionality
        Item::factory()
            ->count(50)
            ->create()
            ->each(function (Item $item) {
                // Create between 1-10 instances per item
                $instanceCount = rand(1, 10);
                $availableCount = 0;
                $borrowedCount = 0;
                
                for ($i = 0; $i < $instanceCount; $i++) {
                    // 70% chance of being available, 30% chance of being borrowed
                    $isAvailable = rand(1, 100) <= 70;
                    
                    $instance = ItemInstance::factory()
                        ->forItem($item)
                        ->create([
                            'status' => $isAvailable ? 'available' : 'borrowed'
                        ]);
                    
                    if ($isAvailable) {
                        $availableCount++;
                    } else {
                        $borrowedCount++;
                    }
                }
                
                // Update item quantities
                $item->update([
                    'total_qty' => $instanceCount,
                    'available_qty' => $availableCount,
                ]);
            });
    }
}