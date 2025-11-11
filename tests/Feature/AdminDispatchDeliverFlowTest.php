<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\BorrowItemInstance;

use PHPUnit\Framework\Attributes\Test;

class AdminDispatchDeliverFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Run migrations
        $this->artisan('migrate');
    }

    #[Test]
    public function dispatch_then_deliver_updates_states_and_stock_correctly()
    {
    // Disable external side-effects only
        \Illuminate\Support\Facades\Notification::fake();
        \Illuminate\Support\Facades\Event::fake();

        // Create distinct borrower and admin users
        $borrower = User::factory()->create(); // role defaults to 'user'
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin); // simulate admin performing dispatch/deliver actions

        // Item fully available (meets 98% availability threshold for dispatch)
        $item = Item::factory()->create([
            'total_qty' => 5,
            'available_qty' => 5,
        ]);

        // Borrow request starts at 'validated' so dispatch will allocate and promote to approved
        $borrowRequest = BorrowRequest::create([
            'user_id' => $borrower->id,
            'borrow_date' => now()->toDateString(),
            'return_date' => now()->addDays(2)->toDateString(),
            'status' => 'validated',
            'delivery_status' => null,
        ]);

        // Request item needing quantity=2
        $bri = BorrowRequestItem::create([
            'borrow_request_id' => $borrowRequest->id,
            'item_id' => $item->id,
            'quantity' => 2,
        ]);

        // Make 5 available instances for allocation
    ItemInstance::factory()->count(5)->available()->forItem($item)->create();
    // Defensive: ensure DB reflects allowed allocated status for sqlite (enum recreated during migration)
    $this->assertDatabaseHas('item_instances', ['item_id' => $item->id, 'status' => 'available']);

        // Sanity check list endpoint reflects validated
        $listResp = $this->getJson('/admin/borrow-requests/list');
        $listResp->assertStatus(200);
        $found = collect($listResp->json())->firstWhere('id', $borrowRequest->id);
        $this->assertNotNull($found, 'Borrow request appears in list');
        $this->assertEquals('validated', $found['status'], 'List endpoint shows validated status');

        // === Dispatch === (via HTTP route to exercise middleware & bindings)
        $dispatchRes = $this->postJson('/admin/borrow-requests/' . $borrowRequest->id . '/dispatch');
        if ($dispatchRes->getStatusCode() !== 200) { $dispatchRes->dump(); }
        $dispatchRes->assertStatus(200)->assertJson(['message' => 'Items dispatched successfully.']);

        $borrowRequest->refresh();
        $item->refresh();

        $this->assertEquals('approved', $borrowRequest->status, 'Status promoted to approved on dispatch');
        $this->assertEquals('dispatched', $borrowRequest->delivery_status, 'Delivery status set to dispatched');
        $this->assertNull($borrowRequest->delivered_at, 'delivered_at remains null after dispatch');
        $this->assertEquals(5, $item->available_qty, 'Stock remains unchanged at dispatch');

        $allocated = BorrowItemInstance::where('borrow_request_id', $borrowRequest->id)->get();
        $this->assertCount(2, $allocated, 'Two instances allocated');
        foreach ($allocated as $row) {
            $this->assertNotNull($row->instance, 'Allocated instance relation present');
            $this->assertEquals('allocated', $row->instance->status, 'Instance marked allocated after dispatch');
        }

        // === Deliver ===
        $deliverRes = $this->postJson('/admin/borrow-requests/' . $borrowRequest->id . '/deliver');
        if ($deliverRes->getStatusCode() !== 200) { $deliverRes->dump(); }
        $deliverRes->assertStatus(200)->assertJson(['message' => 'Items marked as delivered successfully.']);

        $borrowRequest->refresh();
        $item->refresh();

        $this->assertEquals('delivered', $borrowRequest->delivery_status, 'Delivery status updated to delivered');
        $this->assertNotNull($borrowRequest->delivered_at, 'Delivered timestamp set');
        $this->assertEquals(3, $item->available_qty, 'Stock deducted by allocated quantity (2) on delivery');

        // Instances now borrowed
        foreach ($allocated as $row) {
            $row->refresh();
            $this->assertEquals('borrowed', $row->instance->status, 'Allocated instance transitioned to borrowed');
        }

        // === Return Items visibility ===
        // After marking delivered, the request should appear in Return Items list (delivered included in filter)
        $returnList = $this->getJson('/admin/return-items/list');
        $returnList->assertStatus(200);
        $present = collect($returnList->json())->firstWhere('borrow_request_id', $borrowRequest->id);
        $this->assertNotNull($present, 'Delivered request appears in Return Items module list');
        $this->assertEquals('delivered', $present['delivery_status'] ?? null, 'Return Items list reflects delivered status');
    }
}
