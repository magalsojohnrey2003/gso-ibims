<?php

namespace Tests\Feature;

use App\Models\BorrowItemInstance;
use App\Models\BorrowRequest;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReturnItemsTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    protected function createUser(): User
    {
        return User::factory()->create([
            'role' => 'user',
        ]);
    }

    public function test_admin_updates_return_instance_condition_and_syncs_inventory(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $item = Item::create([
            'name' => 'Speaker',
            'category' => 'audio',
            'total_qty' => 2,
            'available_qty' => 1,
        ]);

        $instance = ItemInstance::create([
            'item_id' => $item->id,
            'property_number' => '2024-01-0001-1001',
            'status' => 'borrowed',
        ]);

        $borrowRequest = BorrowRequest::create([
            'user_id' => $user->id,
            'borrow_date' => now()->subDay()->toDateString(),
            'return_date' => now()->addDay()->toDateString(),
            'time_of_usage' => '08:00-12:00',
            'manpower_count' => null,
            'purpose_office' => 'Testing Office',
            'purpose' => 'Integration test scenario',
            'location' => 'Tagoloan, Poblacion, Zone 1',
            'letter_path' => null,
            'status' => 'approved',
            'delivery_status' => 'dispatched',
            'dispatched_at' => now()->subHours(2),
        ]);

        $borrowInstance = BorrowItemInstance::create([
            'borrow_request_id' => $borrowRequest->id,
            'item_id' => $item->id,
            'item_instance_id' => $instance->id,
            'checked_out_at' => now()->subHours(3),
            'expected_return_at' => $borrowRequest->return_date,
            'return_condition' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/admin/return-items/instances/{$borrowInstance->id}", [
                'condition' => 'good',
            ]);

        $response->assertOk()
            ->assertJsonFragment([
                'condition' => 'good',
                'condition_label' => 'Good',
            ]);

        $borrowInstance->refresh();
        $item->refresh();
        $instance->refresh();
        $borrowRequest->refresh();

        $this->assertSame('good', $borrowInstance->return_condition);
        $this->assertNotNull($borrowInstance->returned_at);
        $this->assertSame('available', $instance->status);
        $this->assertSame(2, (int) $item->available_qty);
        $this->assertSame('returned', $borrowRequest->status);
        $this->assertSame('returned', $borrowRequest->delivery_status);
    }
}
