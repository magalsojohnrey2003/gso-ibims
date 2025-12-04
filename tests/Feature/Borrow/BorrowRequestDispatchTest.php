<?php

namespace Tests\Feature\Borrow;

use App\Models\BorrowItemInstance;
use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\User;
use App\Services\PhilSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BorrowRequestDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('user');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_dispatch_blocks_when_item_instance_is_still_borrowed_by_another_request(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $previousBorrower = User::factory()->create(['role' => 'user']);
        $previousBorrower->assignRole('user');

        $item = Item::factory()->create([
            'total_qty' => 1,
            'available_qty' => 0,
            'is_borrowable' => true,
        ]);

        $itemInstance = ItemInstance::factory()
            ->for($item)
            ->state(['status' => 'borrowed'])
            ->create();

        $priorRequest = BorrowRequest::factory()->approved()->create([
            'user_id' => $previousBorrower->id,
            'delivery_status' => 'dispatched',
            'dispatched_at' => Carbon::now()->subDay(),
        ]);

        BorrowItemInstance::factory()
            ->for($priorRequest)
            ->for($item)
            ->for($itemInstance, 'instance')
            ->state([
                'checked_out_at' => Carbon::now()->subDays(2),
                'expected_return_at' => Carbon::now()->addDay(),
                'returned_at' => null,
                'return_condition' => 'pending',
            ])
            ->create();

        $newBorrower = User::factory()->create(['role' => 'user']);
        $newBorrower->assignRole('user');

        $borrowRequest = BorrowRequest::factory()->approved()->create([
            'user_id' => $newBorrower->id,
            'delivery_status' => null,
        ]);

        BorrowItemInstance::factory()
            ->for($borrowRequest)
            ->for($item)
            ->for($itemInstance, 'instance')
            ->state([
                'checked_out_at' => Carbon::now(),
                'expected_return_at' => Carbon::now()->addDays(3),
                'returned_at' => null,
                'return_condition' => 'pending',
            ])
            ->create();

        $this->actingAs($admin);

        $response = $this->postJson(
            route('admin.borrow.requests.dispatch', $borrowRequest)
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Cannot dispatch: Item is currently borrowed by another user. Please mark it as returned/collected first.',
            'item_instance_id' => $itemInstance->id,
        ]);

        $borrowRequest->refresh();
        $this->assertNull($borrowRequest->delivery_status);
    }

    public function test_dispatch_succeeds_when_instances_are_available_or_allocated(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $borrower = User::factory()->create(['role' => 'user']);
        $borrower->assignRole('user');

        $item = Item::factory()->create([
            'total_qty' => 1,
            'available_qty' => 1,
            'is_borrowable' => true,
        ]);

        $itemInstance = ItemInstance::factory()
            ->for($item)
            ->available()
            ->create();

        $borrowRequest = BorrowRequest::factory()->validated()->create([
            'user_id' => $borrower->id,
            'delivery_status' => null,
        ]);

        BorrowRequestItem::factory()
            ->for($borrowRequest, 'request')
            ->for($item)
            ->create(['quantity' => 1]);

        $smsService = Mockery::mock(PhilSmsService::class);
        $smsService->shouldReceive('notifyBorrowerStatus')
            ->once()
            ->with(Mockery::type(BorrowRequest::class), 'approved');

        $this->app->instance(PhilSmsService::class, $smsService);

        $this->actingAs($admin);

        $response = $this->postJson(
            route('admin.borrow.requests.dispatch', $borrowRequest)
        );

        $response->assertOk();
        $response->assertJson(['message' => 'Items dispatched successfully.']);

        $borrowRequest->refresh();
        $this->assertSame('approved', $borrowRequest->status);
        $this->assertSame('dispatched', $borrowRequest->delivery_status);

        $itemInstance->refresh();
        $this->assertSame('allocated', $itemInstance->status);

        $this->assertDatabaseHas('borrow_item_instances', [
            'borrow_request_id' => $borrowRequest->id,
            'item_instance_id' => $itemInstance->id,
        ]);
    }
}
