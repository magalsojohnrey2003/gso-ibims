<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\Office;
use App\Services\PropertyNumberService;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = FakerFactory::create();

        $this->seedCategoryBlueprint();
        $this->seedOfficeBlueprint();

        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->get()
            ->filter(fn ($category) => $category->children->isNotEmpty())
            ->values();

        $offices = Office::all();

        if ($categories->isEmpty() || $offices->isEmpty()) {
            return;
        }

        $categoryLookup = $categories->mapWithKeys(fn ($category) => [$category->name => $category]);
        $propertyNumbers = app(PropertyNumberService::class);

        $this->seedSpecificInventory($categoryLookup, $offices, $propertyNumbers, $faker);

        Item::factory()
            ->count(40)
            ->state(function () use ($categories, $faker) {
                $selected = $categories->random();
                return [
                    'category' => $selected->name,
                    'description' => $faker->sentence(12),
                    'is_borrowable' => true,
                ];
            })
            ->create()
            ->each(function (Item $item) use ($categoryLookup, $offices, $faker, $propertyNumbers) {
                $category = $categoryLookup[$item->category] ?? $categoryLookup->random();
                $categoryCode = str_pad(preg_replace('/\D/', '', (string) $category->category_code), 4, '0', STR_PAD_LEFT);

                $instanceCount = $faker->numberBetween(3, 10);
                $serialSeed = $faker->numberBetween(1, 9000);

                for ($i = 0; $i < $instanceCount; $i++) {
                    $gla = $category->children->random();
                    $glaCode = str_pad(preg_replace('/\D/', '', (string) $gla->category_code), 4, '0', STR_PAD_LEFT);
                    $office = $offices->random();

                    $year = (string) $faker->numberBetween(2020, (int) date('Y'));
                    $serialInt = $serialSeed + $i;
                    $serial = str_pad($serialInt, 4, '0', STR_PAD_LEFT);

                    $propertyNumber = $propertyNumbers->assemble([
                        'year' => $year,
                        'category' => $categoryCode,
                        'gla' => $glaCode,
                        'serial' => $serial,
                        'office' => $office->code,
                    ]);

                    ItemInstance::create([
                        'item_id' => $item->id,
                        'property_number' => $propertyNumber,
                        'serial_no' => $faker->optional(0.6)->regexify('[A-Z0-9]{3,6}'),
                        'model_no' => $faker->optional(0.6)->regexify('[A-Z0-9]{3,8}'),
                        'notes' => $faker->boolean(30) ? $faker->sentence(8) : null,
                        'status' => 'available',
                        'office_code' => $office->code,
                    ]);
                }

                $item->forceFill([
                    'total_qty' => $instanceCount,
                    'available_qty' => $instanceCount,
                ])->saveQuietly();
            });
    }

    protected function seedSpecificInventory(Collection $categoryLookup, Collection $offices, PropertyNumberService $propertyNumbers, \Faker\Generator $faker): void
    {
        $items = [
            ['name' => 'GRASS MOWER', 'category' => 'Grounds & Maintenance', 'gla' => 'Lawn & Garden', 'quantity' => 10],
            ['name' => 'MOVABLE STAGE', 'category' => 'Events Equipment', 'gla' => 'Stages & Seating', 'quantity' => 10],
            ['name' => 'METAL BLEACHERS', 'category' => 'Events Equipment', 'gla' => 'Stages & Seating', 'quantity' => 10],
            ['name' => 'LONG TABLE(s)', 'category' => 'Events Equipment', 'gla' => 'Tables & Fixtures', 'quantity' => 20],
            ['name' => 'MB CHAIRS', 'category' => 'Events Equipment', 'gla' => 'Stages & Seating', 'quantity' => 40],
            ['name' => 'WATER DISPENSER', 'category' => 'Events Equipment', 'gla' => 'Support Utilities', 'quantity' => 10],
            ['name' => 'PARABOLIC TENT', 'category' => 'Events Equipment', 'gla' => 'Tents & Shelters', 'quantity' => 10],
            ['name' => 'TENT(s)', 'category' => 'Events Equipment', 'gla' => 'Tents & Shelters', 'quantity' => 20],
            ['name' => 'FOLDABLE TABLE(s)', 'category' => 'Events Equipment', 'gla' => 'Tables & Fixtures', 'quantity' => 20],
            ['name' => 'TOOL(s)', 'category' => 'Grounds & Maintenance', 'gla' => 'Tools', 'quantity' => 20],
        ];

        foreach ($items as $itemData) {
            $category = $categoryLookup->get($itemData['category']);
            if (! $category || $category->children->isEmpty()) {
                continue;
            }

            $gla = $category->children->firstWhere('name', $itemData['gla']) ?? $category->children->first();
            if (! $gla) {
                continue;
            }

            $instanceCount = max(1, (int) ($itemData['quantity'] ?? 10));

            $item = Item::updateOrCreate(
                ['name' => $itemData['name']],
                [
                    'category' => $category->name,
                    'description' => $itemData['description'] ?? $itemData['name'],
                    'is_borrowable' => true,
                    'photo' => 'images/item.png',
                ]
            );

            ItemInstance::where('item_id', $item->id)->delete();

            $categoryCode = str_pad(preg_replace('/\D/', '', (string) $category->category_code), 4, '0', STR_PAD_LEFT);
            $glaCode = str_pad(preg_replace('/\D/', '', (string) $gla->category_code), 4, '0', STR_PAD_LEFT);
            $serialSeed = $faker->numberBetween(1000, 9000);

            for ($i = 0; $i < $instanceCount; $i++) {
                $office = $offices->random();
                $serial = str_pad($serialSeed + $i, 4, '0', STR_PAD_LEFT);
                $year = (string) $faker->numberBetween(2020, (int) date('Y'));

                $propertyNumber = $propertyNumbers->assemble([
                    'year' => $year,
                    'category' => $categoryCode,
                    'gla' => $glaCode,
                    'serial' => $serial,
                    'office' => $office->code,
                ]);

                ItemInstance::create([
                    'item_id' => $item->id,
                    'property_number' => $propertyNumber,
                    'serial_no' => $faker->optional(0.6)->regexify('[A-Z0-9]{3,6}'),
                    'model_no' => $faker->optional(0.6)->regexify('[A-Z0-9]{3,8}'),
                    'notes' => $faker->boolean(30) ? $faker->sentence(8) : null,
                    'status' => 'available',
                    'office_code' => $office->code,
                ]);
            }

            $item->forceFill([
                'total_qty' => $instanceCount,
                'available_qty' => $instanceCount,
            ])->saveQuietly();
        }
    }

    protected function seedCategoryBlueprint(): void
    {
        $categories = [
            [
                'name' => 'Information Technology Equipment',
                'code' => '1001',
                'glas' => [
                    ['name' => 'Desktop Computers', 'code' => '0101'],
                    ['name' => 'Laptop Computers', 'code' => '0102'],
                    ['name' => 'Networking Devices', 'code' => '0103'],
                ],
            ],
            [
                'name' => 'Office Furniture and Fixtures',
                'code' => '2002',
                'glas' => [
                    ['name' => 'Ergonomic Chairs', 'code' => '0201'],
                    ['name' => 'Conference Tables', 'code' => '0202'],
                    ['name' => 'Storage Cabinets', 'code' => '0203'],
                ],
            ],
            [
                'name' => 'Audio Visual Equipment',
                'code' => '3003',
                'glas' => [
                    ['name' => 'Projectors', 'code' => '0301'],
                    ['name' => 'Sound Systems', 'code' => '0302'],
                    ['name' => 'Cameras', 'code' => '0303'],
                ],
            ],
            [
                'name' => 'Events Equipment',
                'code' => '4004',
                'glas' => [
                    ['name' => 'Stages & Seating', 'code' => '0401'],
                    ['name' => 'Tents & Shelters', 'code' => '0402'],
                    ['name' => 'Tables & Fixtures', 'code' => '0403'],
                    ['name' => 'Support Utilities', 'code' => '0404'],
                ],
            ],
            [
                'name' => 'Grounds & Maintenance',
                'code' => '5005',
                'glas' => [
                    ['name' => 'Lawn & Garden', 'code' => '0501'],
                    ['name' => 'Tools', 'code' => '0502'],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = Category::updateOrCreate(
                ['name' => $categoryData['name'], 'parent_id' => null],
                ['category_code' => $categoryData['code']]
            );

            foreach ($categoryData['glas'] as $glaData) {
                Category::updateOrCreate(
                    ['name' => $glaData['name'], 'parent_id' => $category->id],
                    ['category_code' => $glaData['code']]
                );
            }
        }
    }

    protected function seedOfficeBlueprint(): void
    {
        $offices = [
            ['code' => '0001', 'name' => 'Office of the Mayor'],
            ['code' => '0002', 'name' => 'City Administrator'],
            ['code' => '0003', 'name' => 'Budget Office'],
            ['code' => '0004', 'name' => 'General Services Office'],
            ['code' => '0005', 'name' => 'Human Resource Management'],
            ['code' => '0006', 'name' => 'City Engineering Office'],
        ];

        foreach ($offices as $officeData) {
            Office::updateOrCreate(
                ['code' => $officeData['code']],
                ['name' => $officeData['name']]
            );
        }
    }
}