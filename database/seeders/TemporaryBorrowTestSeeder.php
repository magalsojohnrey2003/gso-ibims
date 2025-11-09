<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\BorrowItemInstance;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\User;

class TemporaryBorrowTestSeeder extends Seeder
{
    /**
     * Run the database seeds for testing borrow request tables.
     */
    public function run(): void
    {
        $this->command->info('Creating temporary test borrow requests for table testing...');
        
        // Get some users and items to work with
        $users = User::where('role', 'user')->get();
        $items = Item::with('instances')->take(20)->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Creating a test user...');
            $users = collect([User::factory()->create(['role' => 'user'])]);
        }
        
        if ($items->isEmpty()) {
            $this->command->warn('No items found. Please run TemporaryItemTestSeeder first.');
            return;
        }
        
        $statuses = ['pending', 'validated', 'approved', 'returned', 'rejected'];
        $deliveryStatuses = ['pending', 'dispatched', 'delivered'];
        
        // Create 50 borrow requests
        for ($i = 0; $i < 50; $i++) {
            $user = $users->random();
            $borrowDate = fake()->dateTimeBetween('-3 months', '+1 month');
            $returnDate = fake()->dateTimeBetween($borrowDate, '+2 months');
            $status = fake()->randomElement($statuses);
            
            $borrowRequest = BorrowRequest::create([
                'user_id' => $user->id,
                'borrow_date' => $borrowDate,
                'return_date' => $returnDate,
                'manpower_count' => rand(1, 10),
                'location' => fake()->address(),
                'purpose_office' => fake()->company(),
                'purpose' => fake()->sentence(),
                'time_of_usage' => fake()->time(),
                'status' => $status,
                'delivery_status' => $status === 'approved' ? fake()->randomElement($deliveryStatuses) : 'pending',
                'dispatched_at' => in_array($status, ['approved', 'returned']) ? fake()->dateTimeBetween('-2 months', 'now') : null,
                'delivered_at' => $status === 'returned' ? fake()->dateTimeBetween('-2 months', 'now') : null,
            ]);
            
            // Add 1-5 items to each request
            $requestItems = $items->random(rand(1, 5));
            
            foreach ($requestItems as $item) {
                $quantity = rand(1, min(3, $item->available_qty ?: 1));
                
                BorrowRequestItem::create([
                    'borrow_request_id' => $borrowRequest->id,
                    'item_id' => $item->id,
                    'quantity' => $quantity,
                ]);
                
                // If approved/returned, create borrow item instances
                if (in_array($status, ['approved', 'returned'])) {
                    $availableInstances = $item->instances()
                        ->where('status', 'available')
                        ->take($quantity)
                        ->get();
                    
                    foreach ($availableInstances as $instance) {
                        BorrowItemInstance::create([
                            'borrow_request_id' => $borrowRequest->id,
                            'item_id' => $item->id,
                            'item_instance_id' => $instance->id,
                            'checked_out_at' => $borrowRequest->dispatched_at,
                            'expected_return_at' => $borrowRequest->return_date,
                            'returned_at' => $status === 'returned' ? $borrowRequest->returned_at : null,
                        ]);
                        
                        // Update instance status
                        $instance->update([
                            'status' => $status === 'returned' ? 'available' : 'borrowed'
                        ]);
                    }
                }
            }
        }
        
        $this->command->info('Created 50 test borrow requests for table testing!');
    }
}