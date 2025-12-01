<?php

namespace Tests\Feature\Borrow;

use App\Models\BorrowRequest;
use App\Models\Item;
use App\Models\ManpowerRole;
use App\Models\User;
use App\Notifications\RequestNotification;
use App\Services\PhilSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BorrowRequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('user');
        Role::findOrCreate('admin');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_user_can_submit_borrow_request_with_items_and_manpower(): void
    {
        Storage::fake('public');
        Notification::fake();

        $user = User::factory()->create(['role' => 'user']);
        $user->assignRole('user');

        $admin = User::factory()->create([
            'role' => 'admin',
            'phone' => '09123456789',
        ]);
        $admin->assignRole('admin');

        $item = Item::factory()->create([
            'is_borrowable' => true,
            'total_qty' => 5,
            'available_qty' => 5,
        ]);

        $manpowerRole = ManpowerRole::factory()->create();

        $this->actingAs($user);

        session(['borrowList' => [
            $item->id => [
                'id' => $item->id,
                'name' => $item->name,
                'photo' => $item->photo ?? 'images/item.png',
                'qty' => 2,
                'total_qty' => 5,
                'safe_max_qty' => 5,
                'available_qty' => 5,
                'category' => $item->category,
            ],
        ]]);

        $smsService = Mockery::mock(PhilSmsService::class);
        $smsService->shouldReceive('notifyAdminsBorrowRequest')
            ->once()
            ->with(Mockery::type(BorrowRequest::class));

        $this->app->instance(PhilSmsService::class, $smsService);

        $borrowDate = now()->addDays(3)->toDateString();
        $returnDate = now()->addDays(5)->toDateString();

        $response = $this->post(route('borrowList.submit'), [
            'borrow_date' => $borrowDate,
            'return_date' => $returnDate,
            'time_of_usage' => '08:00 - 12:00',
            'location' => 'City Hall',
            'purpose_office' => 'City Events',
            'purpose' => 'For barangay assembly',
            'support_letter' => UploadedFile::fake()->create('letter.pdf', 120, 'application/pdf'),
            'items' => [
                $item->id => ['quantity' => 2],
            ],
            'manpower_requirements' => [
                ['role_id' => $manpowerRole->id, 'quantity' => 3],
            ],
        ]);

        $response->assertRedirect(route('borrow.items'));
        $response->assertSessionHas('success');

        /** @var BorrowRequest $borrowRequest */
        $borrowRequest = BorrowRequest::first();
        $this->assertNotNull($borrowRequest, 'Borrow request was not stored.');

        Storage::disk('public')->assertExists($borrowRequest->letter_path);
        $this->assertDatabaseHas('borrow_requests', [
            'id' => $borrowRequest->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'delivery_status' => 'pending',
        ]);

        $this->assertDatabaseHas('borrow_request_items', [
            'borrow_request_id' => $borrowRequest->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'is_manpower' => 0,
        ]);

        $this->assertDatabaseHas('borrow_request_items', [
            'borrow_request_id' => $borrowRequest->id,
            'is_manpower' => 1,
            'manpower_role_id' => $manpowerRole->id,
            'quantity' => 3,
        ]);

        $this->assertEquals([], session('borrowList', []));

        Notification::assertSentTo(
            $admin,
            RequestNotification::class,
            function (RequestNotification $notification, array $channels) use ($borrowRequest) {
                return ($notification->payload['type'] ?? null) === 'borrow_submitted'
                    && ($notification->payload['borrow_request_id'] ?? null) === $borrowRequest->id;
            }
        );
    }
}
