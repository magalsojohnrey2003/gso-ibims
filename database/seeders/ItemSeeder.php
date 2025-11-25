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
        Item::factory()
            ->count(50)
            ->create()
            ->each(function (Item $item) {
                $instanceCount = rand(3, 12);

                ItemInstance::factory()
                    ->count($instanceCount)
                    ->forItem($item)
                    ->available()
                    ->create();

                $item->forceFill([
                    'total_qty' => $instanceCount,
                    'available_qty' => $instanceCount,
                ])->saveQuietly();
            });
    }
}