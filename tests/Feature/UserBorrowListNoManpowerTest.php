<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Item;
use App\Models\BorrowRequest;

use PHPUnit\Framework\Attributes\Test;

class UserBorrowListNoManpowerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        Storage::fake('public');
    }

    #[Test]
    public function user_can_submit_borrow_request_without_manpower_count()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        // Create item with available stock
        $item = Item::factory()->create([
            'total_qty' => 10,
            'available_qty' => 10,
        ]);

        // Seed session borrow list
        $borrowList = [
            $item->id => [
                'id' => $item->id,
                'name' => $item->name,
                'photo' => 'images/item.png',
                'qty' => 2,
                'total_qty' => $item->total_qty,
                'category' => $item->category,
            ],
        ];
        Session::put('borrowList', $borrowList);

        $borrowDate = now()->addDay()->toDateString();
        $returnDate = now()->addDays(2)->toDateString();

        $payload = [
            'borrow_date' => $borrowDate,
            'return_date' => $returnDate,
            'time_of_usage' => '09:00-17:00',
            // manpower_count intentionally omitted
            'location' => 'Sample Town, Barangay 1, Purok 2',
            'purpose_office' => 'Engineering Office – Maintenance Team',
            'purpose' => 'Routine maintenance of equipment',
            'items' => [
                $item->id => ['quantity' => 2],
            ],
            'support_letter' => UploadedFile::fake()->create('letter.pdf', 10, 'application/pdf'),
        ];

        $response = $this->post('/user/borrow-list/submit', $payload);

        // Expect redirect to borrow items page on success
        $response->assertStatus(302);
        $response->assertRedirect('/user/borrow-items');

        // Borrow request created without manpower_count
        // Database stores dates with time component (00:00:00) due to casting; assert using full stored form.
        $this->assertDatabaseHas('borrow_requests', [
            'user_id' => $user->id,
            'borrow_date' => $borrowDate . ' 00:00:00',
            'return_date' => $returnDate . ' 00:00:00',
            'purpose_office' => 'Engineering Office – Maintenance Team',
            'purpose' => 'Routine maintenance of equipment',
            'location' => 'Sample Town, Barangay 1, Purok 2',
            'status' => 'pending',
        ]);

        $br = BorrowRequest::latest()->first();
        $this->assertNotNull($br, 'Borrow request was created');
        $this->assertNull($br->manpower_count, 'manpower_count should be null when omitted');
    }
}
