<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalkInRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_create_walk_in_request_and_it_appears_in_list()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::factory()->create(['total_qty' => 10, 'available_qty' => 10]);

        $payload = [
            'borrower_name' => 'Juan Dela Cruz',
            'office_agency' => 'Engineering Office',
            'contact_number' => '09171234567',
            'address' => 'Sample Address',
            'purpose' => 'Emergency generator use',
            'borrowed_at' => now()->toDateTimeString(),
            'returned_at' => now()->addDay()->toDateTimeString(),
            'items' => [
                ['id' => $item->id, 'quantity' => 2],
            ],
        ];

        $response = $this->actingAs($admin)->postJson(route('admin.walkin.store'), $payload);
        $response->assertCreated();
        $id = $response->json('id');
        $this->assertNotNull($id, 'Walk-in ID should be returned');

        $list = $this->actingAs($admin)->getJson(route('admin.walkin.list'));
        $list->assertOk();
        $list->assertJsonFragment(['borrower_name' => 'Juan Dela Cruz']);
    }
}
