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
        // Create 50 items, all instances 100% available
        Item::factory()
            ->count(50)
            ->create()
            ->each(function (Item $item) {
                // Create between 1-10 instances per item
                $instanceCount = rand(1, 10);
                for ($i = 0; $i < $instanceCount; $i++) {
                    ItemInstance::factory()
                        ->forItem($item)
                        ->create([
                            'status' => 'available'
                        ]);
                }
                // Update item quantities
                $item->update([
                    'total_qty' => $instanceCount,
                    'available_qty' => $instanceCount,
                ]);
            });
    }
}