<?php

namespace Tests\Feature\ReturnItems;

use App\Models\BorrowItemInstance;
use App\Models\BorrowRequest;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReturnItemsCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
    }

    public function test_admin_can_mark_borrow_request_items_as_returned(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'first_name' => 'Admin',
        ]);
        $admin->assignRole('admin');

        $item = Item::factory()->create([
            'is_borrowable' => true,
            'total_qty' => 2,
            'available_qty' => 0,
        ]);

        $itemInstance = ItemInstance::factory()
            ->for($item)
            ->borrowed()
            ->create();

        $borrowRequest = BorrowRequest::factory()
            ->approved()
            ->create([
                'user_id' => User::factory(),
                'delivery_status' => 'delivered',
                'delivered_at' => Carbon::now()->subDay(),
            ]);

        $borrowItemInstance = BorrowItemInstance::factory()
            ->for($borrowRequest)
            ->for($item)
            ->for($itemInstance, 'instance')
            ->state([
                'checked_out_at' => Carbon::now()->subDays(2),
                'expected_return_at' => Carbon::now()->addDay(),
                'returned_at' => null,
                'return_condition' => 'pending',
            ])
            ->create();

        $this->actingAs($admin);

        $response = $this->postJson(route('admin.return-items.collect', $borrowRequest));

        $response->assertOk();
        $response->assertJson([
            'status' => 'returned',
            'delivery_status' => 'returned',
            'borrow_request_id' => $borrowRequest->id,
        ]);

        $borrowRequest->refresh();
        $this->assertSame('returned', $borrowRequest->status);
        $this->assertSame('returned', $borrowRequest->delivery_status);

        $borrowItemInstance->refresh();
        $this->assertSame('good', $borrowItemInstance->return_condition);
        $this->assertNotNull($borrowItemInstance->returned_at);

        $itemInstance->refresh();
        $this->assertSame('available', $itemInstance->status);

        $item->refresh();
        $this->assertSame(1, $item->available_qty, 'Available quantity should increment after return.');

        $this->assertDatabaseHas('item_instance_events', [
            'item_instance_id' => $itemInstance->id,
            'action' => 'returned',
        ]);
    }
}
