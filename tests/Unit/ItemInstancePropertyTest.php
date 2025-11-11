<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Models\ItemInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemInstancePropertyTest extends TestCase
{
    use RefreshDatabase;

    private function createItem(): Item
    {
        return Item::create([
            'name' => 'Office Chair',
            'category' => 'furniture',
            'total_qty' => 10,
            'available_qty' => 10,
            'photo' => null,
        ]);
    }

    public function test_property_number_mutator_populates_components(): void
    {
        $item = $this->createItem();

        $instance = new ItemInstance([
            'item_id' => $item->id,
            'status' => 'available',
            'notes' => null,
        ]);

        $instance->property_number = '2020-05-0660-8831';
        $instance->save();

        $this->assertSame('2020-05-0660-8831', $instance->property_number);
        $this->assertSame(2020, $instance->year_procured);
    $this->assertSame('05', $instance->category_code);
        $this->assertSame('0660', $instance->serial);
        $this->assertSame(660, $instance->serial_int);
        $this->assertSame('8831', $instance->office_code);
    }

    public function test_scope_search_property_supports_various_patterns(): void
    {
        $item = $this->createItem();

        $a = ItemInstance::create([
            'item_id' => $item->id,
            'status' => 'available',
            'property_number' => '2020-05-0660-8831',
            'notes' => null,
        ]);

        $b = ItemInstance::create([
            'item_id' => $item->id,
            'status' => 'available',
            'property_number' => '2020-05-0661-8831',
            'notes' => null,
        ]);

        $c = ItemInstance::create([
            'item_id' => $item->id,
            'status' => 'available',
            'property_number' => '2021-07-0100-1100',
            'notes' => null,
        ]);

        $exact = ItemInstance::searchProperty('2020-05-0660-8831')->pluck('id');
        $this->assertTrue($exact->contains($a->id));
        $this->assertFalse($exact->contains($b->id));

        $partial = ItemInstance::searchProperty('2020-05-066')->pluck('id');
        $this->assertTrue($partial->contains($a->id));
        $this->assertTrue($partial->contains($b->id));

        $numeric = ItemInstance::searchProperty('0661')->pluck('id');
        $this->assertTrue($numeric->contains($b->id));
        $this->assertFalse($numeric->contains($a->id));

        $serialInt = ItemInstance::searchProperty('100')->pluck('id');
        $this->assertTrue($serialInt->contains($c->id));
    }
}
