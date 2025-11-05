<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
                'category_code' => '0005',
                'year_procured' => '2020',
                'office_code' => '8831',
                'start_serial' => '0660',
                'gla' => '225',
                'quantity' => 3,
                'description' => 'Thin and light laptop.',
            ]);

        $response->assertCreated();
        $response->assertJson([
            'created_count' => 3,
            'skipped_serials' => [],
        ]);

        $expected = [
            '2020-0005-225-0660-8831',
            '2020-0005-225-0661-8831',
            '2020-0005-225-0662-8831',
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
            'serial' => '0660',
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

    public function test_large_quantity_item_has_matching_available_stock(): void
    {
        $admin = $this->createAdmin();

        $payload = [
            'name' => 'High Volume Storage Bins',
            'category' => 'Storage',
            'category_code' => '0005',
            'year_procured' => '2024',
            'office_code' => '1234',
            'start_serial' => '0001',
            'gla' => '225',
            'quantity' => 250,
            'description' => 'Bulk storage bins for warehouse.',
        ];

        $response = $this->actingAs($admin)->postJson('/admin/items', $payload);

        $response->assertCreated();

        $item = Item::where('name', $payload['name'])->firstOrFail();
        $item->refresh();

        $this->assertSame(250, (int) $item->total_qty, 'Total quantity should match submitted quantity.');
        $this->assertSame(250, (int) $item->available_qty, 'Available quantity should match submitted quantity.');
        $this->assertSame(
            250,
            ItemInstance::where('item_id', $item->id)->where('status', 'available')->count(),
            'Expected all generated instances to remain available.'
        );
    }

    public function test_photo_upload_is_saved_and_used_for_item(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();

        $photo = UploadedFile::fake()->image('custom-item.jpg', 800, 600);

        $payload = [
            'name' => 'Camera Tripod',
            'category' => 'Photography',
            'quantity' => 5,
            'description' => 'Sturdy tripod stand for DSLR cameras.',
            'photo' => $photo,
        ];

        $response = $this->actingAs($admin)->post(
            '/admin/items',
            $payload,
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'HTTP_ACCEPT' => 'application/json']
        );

        $response->assertCreated();

        $item = Item::where('name', $payload['name'])->firstOrFail();
        $this->assertNotNull($item->photo, 'Photo path should be stored on the item.');
        $this->assertNotSame('images/item.png', $item->photo, 'Uploaded photo should replace the default placeholder.');

        Storage::disk('public')->assertExists($item->photo);
    }
}
