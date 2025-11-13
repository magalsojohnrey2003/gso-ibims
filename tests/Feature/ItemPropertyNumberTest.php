<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // Create a Category so the category code lookup succeeds
        \App\Models\Category::create([
            'name' => 'Laptop',
            'category_code' => '0005',
            'parent_id' => null,
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/admin/items', [
                'name' => 'Dell XPS 13',
                'category' => 'Laptop',
                'category_code' => '0005', // Explicit category code to avoid resolution issues
                'year_procured' => '2020',
                'office_code' => '8831',
                'start_serial' => '0660',
                'quantity' => 3,
                'description' => 'Thin and light laptop.',
                'gla' => '1234', // GLA is required for bulk generation
            ]);

        $response->assertCreated();
        $response->assertJson([
            'created_count' => 3,
            'skipped_serials' => [],
        ]);

        $expected = [
            '2020-0005-1234-0660-8831',
            '2020-0005-1234-0661-8831',
            '2020-0005-1234-0662-8831',
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

}
