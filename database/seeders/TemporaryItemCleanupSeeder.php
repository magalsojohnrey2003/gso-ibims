<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\ItemInstance;
use Illuminate\Support\Facades\DB;

class TemporaryItemCleanupSeeder extends Seeder
{
    /**
     * Remove temporary test data created by TemporaryItemTestSeeder.
     */
    public function run(): void
    {
        $this->command->info('Cleaning up temporary test data...');
        
        // Get count before cleanup
        $itemCount = Item::count();
        $instanceCount = ItemInstance::count();
        $borrowRequestCount = \App\Models\BorrowRequest::count();
        
        $this->command->info("Found {$itemCount} items, {$instanceCount} instances, and {$borrowRequestCount} borrow requests before cleanup.");
        
        // Delete all records created in the last 2 hours (assuming test data was just created)
        $cutoffTime = now()->subHours(2);
        
        $deletedBorrowRequests = \App\Models\BorrowRequest::where('created_at', '>', $cutoffTime)->delete();
        $deletedItems = Item::where('created_at', '>', $cutoffTime)->delete();
        
        $this->command->info("Deleted {$deletedItems} test items and their instances.");
        $this->command->info("Deleted {$deletedBorrowRequests} test borrow requests.");
        $this->command->info('Cleanup complete!');
    }
}