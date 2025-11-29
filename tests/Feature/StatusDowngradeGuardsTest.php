<?php

namespace Tests\Feature;

use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusDowngradeGuardsTest extends TestCase
{
    use RefreshDatabase;

    private function setupDeliveredRequest(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $borrower = User::factory()->create();
        $this->actingAs($admin);

        $item = Item::factory()->create(['total_qty' => 4, 'available_qty' => 4]);
        $req = BorrowRequest::create([
            'user_id' => $borrower->id,
            'borrow_date' => now()->toDateString(),
            'return_date' => now()->addDays(1)->toDateString(),
            'status' => 'validated',
        ]);
        BorrowRequestItem::create(['borrow_request_id' => $req->id, 'item_id' => $item->id, 'quantity' => 1]);
        ItemInstance::factory()->count(4)->available()->forItem($item)->create();

        $this->postJson('/admin/borrow-requests/' . $req->id . '/dispatch')->assertStatus(200);
        $this->actingAs($borrower);
        $this->postJson('/user/my-borrowed-items/' . $req->id . '/confirm-delivery')->assertStatus(200);
        $this->actingAs($admin);

        $req->refresh();
        $item->refresh();
        return [$admin, $borrower, $item, $req];
    }

    #[Test]
    public function cannot_change_status_backwards_after_delivered()
    {
        [, , , $req] = $this->setupDeliveredRequest();

        // Try to move back to approved
        $res1 = $this->postJson('/admin/borrow-requests/' . $req->id . '/update-status', ['status' => 'approved']);
        $res1->assertStatus(422)->assertJson(['message' => 'Cannot modify request status after delivery except to handle returns.']);

        // Try to move back to validated
        $res2 = $this->postJson('/admin/borrow-requests/' . $req->id . '/update-status', ['status' => 'validated']);
        $res2->assertStatus(422);

        // Allowed: move to return stages
        $res3 = $this->postJson('/admin/borrow-requests/' . $req->id . '/update-status', ['status' => 'return_pending']);
        $res3->assertStatus(200);
    }

    #[Test]
    public function cannot_downgrade_after_dispatch()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $borrower = User::factory()->create();
        $this->actingAs($admin);

        $item = Item::factory()->create(['total_qty' => 3, 'available_qty' => 3]);
        $req = BorrowRequest::create([
            'user_id' => $borrower->id,
            'borrow_date' => now()->toDateString(),
            'return_date' => now()->addDays(1)->toDateString(),
            'status' => 'validated',
        ]);
        BorrowRequestItem::create(['borrow_request_id' => $req->id, 'item_id' => $item->id, 'quantity' => 1]);
        ItemInstance::factory()->count(3)->available()->forItem($item)->create();

        $this->postJson('/admin/borrow-requests/' . $req->id . '/dispatch')->assertStatus(200);
        $req->refresh();

        // Attempt to downgrade to pending should be blocked
        $res = $this->postJson('/admin/borrow-requests/' . $req->id . '/update-status', ['status' => 'pending']);
        $res->assertStatus(422)->assertJson(['message' => 'Cannot downgrade status after dispatch.']);
    }
}
