<?php

namespace Tests\Feature;

use App\Models\BorrowItemInstance;
use App\Models\BorrowRequest;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItemPropertyNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(): User
    {
        return User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);
    }

    protected function createItem(): Item
    {
        return Item::create([
            'name' => 'Office Chair Model A',
            'category' => 'furniture',
            'total_qty' => 0,
            'available_qty' => 0,
            'photo' => null,
        ]);
    }

    public function test_admin_bulk_create_generates_property_numbers(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/admin/items', [
                'name' => 'Dell XPS 13',
                'category' => 'Laptop',
                'year_procured' => '2020',
                'office_code' => '8831',
                'start_serial' => '0660',
                'quantity' => 3,
                'description' => 'Thin and light laptop.',
            ]);

        $response->assertCreated();
        $response->assertJson([
            'created_count' => 3,
            'skipped_serials' => [],
        ]);

        $expected = [
            '2020-05-0660-8831',
            '2020-05-0661-8831',
            '2020-05-0662-8831',
        ];

        $this->assertSame($expected, ItemInstance::orderBy('serial_int')->pluck('property_number')->toArray());
    }

    public function test_admin_property_search_returns_exact_match(): void
    {
        $admin = $this->createAdmin();
        $item = $this->createItem();

        ItemInstance::create([
            'item_id' => $item->id,
            'property_number' => '2020-05-0660-8831',
            'status' => 'available',
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/admin/items/search?q=2020-05-0660-8831');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'property_number' => '2020-05-0660-8831',
                'item_id' => $item->id,
            ]);
    }

    public function test_user_property_search_returns_active_borrow_status(): void
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@example.com',
            'password' => bcrypt('secret'),
            'role' => 'user',
        ]);
        $item = $this->createItem();

        $instance = ItemInstance::create([
            'item_id' => $item->id,
            'property_number' => '2020-05-0660-8831',
            'status' => 'borrowed',
        ]);

        $borrowRequest = BorrowRequest::create([
            'user_id' => $user->id,
            'borrow_date' => now()->toDateString(),
            'return_date' => now()->addDays(7)->toDateString(),
            'status' => 'approved',
        ]);

        BorrowItemInstance::create([
            'borrow_request_id' => $borrowRequest->id,
            'item_id' => $item->id,
            'item_instance_id' => $instance->id,
            'checked_out_at' => now(),
            'expected_return_at' => now()->addDays(7),
            'returned_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/user/items/search-property?q=0660');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'property_number' => '2020-05-0660-8831',
                'borrow_status' => 'borrowed',
                'borrow_request_id' => $borrowRequest->id,
            ]);
    }

    public function test_user_can_submit_damage_report(): void
    {
        Storage::fake('public');

        $user = User::create([
            'first_name' => 'Demo',
            'last_name' => 'User',
            'email' => 'demo@example.com',
            'password' => bcrypt('secret'),
            'role' => 'user',
        ]);
        $item = $this->createItem();
        $instance = ItemInstance::create([
            'item_id' => $item->id,
            'property_number' => '2020-05-0660-8831',
            'status' => 'borrowed',
        ]);
        $borrowRequest = BorrowRequest::create([
            'user_id' => $user->id,
            'borrow_date' => now()->toDateString(),
            'return_date' => now()->addDays(2)->toDateString(),
            'status' => 'approved',
        ]);

        $payload = [
            'item_instance_id' => $instance->id,
            'borrow_request_id' => $borrowRequest->id,
            'description' => 'Backrest broken',
        ];

        $response = $this->actingAs($user)
            ->postJson('/user/items/damage-reports', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'item_instance_id' => $instance->id,
                'description' => 'Backrest broken',
                'status' => 'reported',
            ]);

        $this->assertDatabaseHas('item_damage_reports', [
            'item_instance_id' => $instance->id,
            'description' => 'Backrest broken',
            'reported_by' => $user->id,
        ]);
    }
}
