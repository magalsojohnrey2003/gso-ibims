<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\ItemInstance;

class TemporaryItemTestSeeder extends Seeder
{
    /**
     * Run the database seeds for testing table functionality.
     * This seeder creates a large number of items to test:
     * - Table scrollbar functionality
     * - Sticky header behavior
     * - Zebra striping appearance
     * - Performance with many records
     */
    public function run(): void
    {
        $this->command->info('Creating temporary test items for table testing...');
        
        // Create 100 test items to ensure we have enough data for scrolling
        Item::factory()
            ->count(100)
            ->create()
            ->each(function (Item $item, $index) {
                // Create varying number of instances per item (1-15)
                $instanceCount = rand(1, 15);
                $availableCount = 0;
                
                for ($i = 0; $i < $instanceCount; $i++) {
                    // Generate unique property numbers to avoid conflicts
                    $year = rand(2020, 2025);
                    $categoryCode = str_pad(rand(1, 50), 2, '0', STR_PAD_LEFT);
                    $gla = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    $serial = str_pad(($index * 100) + $i + 1, 4, '0', STR_PAD_LEFT);
                    $propertyNumber = "{$year}-{$categoryCode}-{$gla}-{$serial}";
                    
                    // Make most items available for better testing
                    $isAvailable = rand(1, 100) <= 80; // 80% available
                    
                    try {
                        ItemInstance::create([
                            'item_id' => $item->id,
                            'property_number' => $propertyNumber,
                            'year_procured' => $year,
                            'category_code' => $categoryCode,
                            'gla' => $gla,
                            'serial' => $serial,
                            'serial_int' => intval($serial),
                            'serial_no' => fake()->optional(0.6)->regexify('[A-Z0-9]{1,4}'),
                            'model_no' => fake()->optional(0.6)->regexify('[A-Z0-9]{3,8}'),
                            'office_code' => str_pad(rand(1, 20), 2, '0', STR_PAD_LEFT),
                            'status' => $isAvailable ? 'available' : 'borrowed',
                            'notes' => fake()->optional(0.2)->sentence(),
                        ]);
                        
                        if ($isAvailable) {
                            $availableCount++;
                        }
                    } catch (\Exception $e) {
                        // Skip if property number already exists
                        continue;
                    }
                }
                
                // Update item quantities
                $item->update([
                    'total_qty' => $instanceCount,
                    'available_qty' => $availableCount,
                ]);
            });
            
        $this->command->info('Created 100 test items with instances for table testing!');
        $this->command->warn('Remember to run "php artisan db:seed --class=TemporaryItemCleanupSeeder" to remove test data when done.');
    }
}