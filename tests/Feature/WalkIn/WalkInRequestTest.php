<?php

namespace Tests\Feature\WalkIn;

use App\Models\BorrowItemInstance;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\User;
use App\Models\WalkInRequest;
use App\Models\WalkInRequestItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WalkInRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
    }

    public function test_admin_can_create_walk_in_request(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'first_name' => 'Manager',
        ]);
        $admin->assignRole('admin');

        $itemA = Item::factory()->create([
            'is_borrowable' => true,
            'available_qty' => 5,
        ]);
        $itemB = Item::factory()->create([
            'is_borrowable' => true,
            'available_qty' => 3,
        ]);

        $payload = [
            'borrower_name' => 'Juan Dela Cruz',
            'office_agency' => 'Barangay Council',
            'contact_number' => '09123456789',
            'address' => 'Barangay Uno',
            'purpose' => 'Community cleanup',
            'borrowed_at' => now()->toDateString(),
            'returned_at' => now()->addDay()->toDateString(),
            'items' => [
                ['id' => $itemA->id, 'quantity' => 2],
                ['id' => $itemB->id, 'quantity' => 1],
            ],
        ];

        $this->actingAs($admin);

        $response = $this->postJson(route('admin.walkin.store'), $payload);

        $response->assertCreated();
        $response->assertJson(['message' => 'Walk-in request created successfully.']);

        $walkInRequest = WalkInRequest::first();
        $this->assertNotNull($walkInRequest);
        $this->assertSame('pending', $walkInRequest->status);
        $this->assertSame($admin->id, $walkInRequest->created_by);

        $this->assertCount(2, WalkInRequestItem::where('walk_in_request_id', $walkInRequest->id)->get());

        $this->assertDatabaseHas('walk_in_request_items', [
            'walk_in_request_id' => $walkInRequest->id,
            'item_id' => $itemA->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('walk_in_request_items', [
            'walk_in_request_id' => $walkInRequest->id,
            'item_id' => $itemB->id,
            'quantity' => 1,
        ]);
    }

    public function test_admin_can_deliver_and_collect_walk_in_request(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'first_name' => 'Manager',
        ]);
        $admin->assignRole('admin');

        $item = Item::factory()->create([
            'is_borrowable' => true,
            'total_qty' => 3,
            'available_qty' => 3,
        ]);

        ItemInstance::factory()->count(3)->for($item)->available()->create();

        $walkInRequest = WalkInRequest::factory()
            ->approved()
            ->create([
                'created_by' => $admin->id,
                'borrowed_at' => now()->subDay(),
                'returned_at' => now()->addDay(),
            ]);

        WalkInRequestItem::factory()->create([
            'walk_in_request_id' => $walkInRequest->id,
            'item_id' => $item->id,
            'quantity' => 2,
        ]);

        $this->actingAs($admin);

        $deliverResponse = $this->postJson(route('admin.walkin.deliver', $walkInRequest->id));

        $deliverResponse->assertOk();
        $deliverResponse->assertJson(['message' => 'Walk-in request marked as delivered. Items deducted from inventory.']);

        $walkInRequest->refresh();
        $this->assertSame('delivered', $walkInRequest->status);

        $item->refresh();
        $this->assertSame(1, $item->available_qty);

        $borrowInstances = BorrowItemInstance::where('walk_in_request_id', $walkInRequest->id)->get();
        $this->assertCount(2, $borrowInstances);
        $this->assertTrue($borrowInstances->every(fn (BorrowItemInstance $instance) => $instance->return_condition === 'pending'));

        $borrowedInstanceIds = $borrowInstances->pluck('item_instance_id');
        $this->assertTrue(
            ItemInstance::whereIn('id', $borrowedInstanceIds)
                ->get()
                ->every(fn (ItemInstance $instance) => $instance->status === 'borrowed')
        );

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA ignore_check_constraints = ON');
        }

        $collectResponse = $this->postJson(route('admin.return-items.collect-walkin', $walkInRequest->id));

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA ignore_check_constraints = OFF');
        }

        $collectResponse->assertOk();
        $collectResponse->assertJson([
            'walk_in_request_id' => $walkInRequest->id,
            'message' => 'Walk-in items marked as returned successfully.',
        ]);

        $walkInRequest->refresh();
        $this->assertSame('returned', $walkInRequest->status);

        $item->refresh();
        $this->assertSame(3, $item->available_qty);

        $borrowInstances->each->refresh();
        $this->assertTrue($borrowInstances->every(fn (BorrowItemInstance $instance) => $instance->return_condition === 'good'));

        $this->assertTrue(
            ItemInstance::whereIn('id', $borrowedInstanceIds)
                ->get()
                ->every(fn (ItemInstance $instance) => $instance->status === 'available')
        );

        $this->assertDatabaseCount('item_instance_events', 2);
    }

    public function test_walk_in_delivery_fails_without_sufficient_inventory(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $item = Item::factory()->create([
            'is_borrowable' => true,
            'total_qty' => 1,
            'available_qty' => 1,
        ]);

        ItemInstance::factory()->for($item)->available()->create();

        $walkInRequest = WalkInRequest::factory()
            ->approved()
            ->create([
                'created_by' => $admin->id,
                'borrowed_at' => now()->subDay(),
                'returned_at' => now()->addDay(),
            ]);

        WalkInRequestItem::factory()->create([
            'walk_in_request_id' => $walkInRequest->id,
            'item_id' => $item->id,
            'quantity' => 2,
        ]);

        $this->actingAs($admin);

        $response = $this->postJson(route('admin.walkin.deliver', $walkInRequest->id));

        $response->assertStatus(500);
        $response->assertJson([
            'message' => 'Insufficient quantity for ' . $item->name . '. Available: 1, Requested: 2',
        ]);

        $walkInRequest->refresh();
        $this->assertSame('approved', $walkInRequest->status);

        $item->refresh();
        $this->assertSame(1, $item->available_qty);

        $this->assertDatabaseCount('borrow_item_instances', 0);
    }
}
