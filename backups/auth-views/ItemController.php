<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Services\PropertyNumberService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    protected array $defaultPhotos = [
        'furniture'   => 'defaults/furniture.png',
        'electronics' => 'defaults/electronics.png',
        'tools'       => 'defaults/tools.png',
        'vehicles'    => 'defaults/vehicles.png',
    ];

    protected array $categoryPpeMap = [
        'Laptop' => '05',
        'Printer' => '06',
        'Chair' => '07',
        'Table' => '08',
        'Projector' => '09',
        'Vehicle' => '10',
        'Furniture' => '07',
        'Tool' => '11',
    ];

    protected function getCategories(): array
    {
        $fromItems = Item::query()->distinct('category')->pluck('category')->filter()->values()->toArray();
        return array_values(array_unique(array_merge(array_keys($this->defaultPhotos), $fromItems)));
    }

    protected function resolvePpeCode(string $category, ?string $fallback = null): string
    {
        $normalized = array_change_key_case($this->categoryPpeMap, CASE_LOWER);
        $code = $normalized[strtolower($category)] ?? null;
        if ($fallback && preg_match('/^\d{2}$/', $fallback)) {
            return $fallback;
        }
        return $code ?? '00';
    }

    public function index(Request $request)
    {
        $query = Item::query()->with('instances');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('created_at', 'desc')->get();
        $categories = $this->getCategories();
        $categoryPpeMap = $this->categoryPpeMap;

        return view('admin.items.index', compact('items', 'categories', 'categoryPpeMap'));
    }

    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if ($term === '') {
            return response()->json([]);
        }

        $instances = ItemInstance::with('item')
            ->searchProperty($term)
            ->limit(20)
            ->get()
            ->map(function (ItemInstance $instance) {
                return [
                    'id' => $instance->id,
                    'property_number' => $instance->property_number,
                    'serial' => $instance->serial,
                    'status' => $instance->status,
                    'item_id' => $instance->item_id,
                    'item_name' => $instance->item?->name,
                    'office_code' => $instance->office_code,
                ];
            });

        return response()->json($instances);
    }

    public function checkSerial(Request $request, PropertyNumberService $numbers)
    {
        $data = $request->validate([
            'year_procured' => 'required|digits:4',
            'office_code' => 'required|digits:4',
            'start_serial' => 'required|digits_between:1,8',
            'quantity' => 'required|integer|min:1|max:500',
            'category' => 'nullable|string',
            'ppe_code' => 'nullable|string|max:20',
            'exclude_instance_id' => 'nullable|integer|exists:item_instances,id',
        ]);

        $category = (string) ($data['category'] ?? '');
        $ppeInput = (string) ($data['ppe_code'] ?? '');
        $ppe = $ppeInput ?: $this->resolvePpeCode($category, $ppeInput ?: null);

        $serialSeed = $data['start_serial'];
        $serialWidth = max(strlen($serialSeed), 4);
        $serialStartRaw = ltrim($serialSeed, '0');
        $serialStart = $serialStartRaw === '' ? 0 : (int) $serialStartRaw;
        $quantity = (int) $data['quantity'];

        $components = [
            'year' => $data['year_procured'],
            'ppe' => $ppe,
            'office' => $data['office_code'],
        ];

        $propertyNumbers = [];
        $serialLookup = [];
        for ($i = 0; $i < $quantity; $i++) {
            $serialInt = $serialStart + $i;
            $serial = $numbers->padSerial($serialInt, $serialWidth);
            $payload = $components;
            $payload['serial'] = $serial;
            $payload['serial_width'] = $serialWidth;
            $propertyNumber = $numbers->assemble($payload);
            $propertyNumbers[] = $propertyNumber;
            $serialLookup[$propertyNumber] = $serial;
        }

        $existing = ItemInstance::query()
            ->whereIn('property_number', $propertyNumbers)
            ->when(! empty($data['exclude_instance_id']), fn ($q) => $q->where('id', '!=', (int) $data['exclude_instance_id']))
            ->pluck('property_number')
            ->all();

        $conflicts = collect($existing)
            ->map(fn ($pn) => $serialLookup[$pn] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'available' => count($conflicts) === 0,
            'conflict_serials' => $conflicts,
            'available_slots' => max(0, $quantity - count($conflicts)),
        ]);
    }

    public function store(Request $request, PropertyNumberService $numbers)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'quantity' => 'required|integer|min:1|max:500',
            'year_procured' => 'required|digits:4',
            'office_code' => 'required|digits:4',
            'start_serial' => 'required|digits_between:1,8',
            'description' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:2048',
        ]);

        $ppeCode = $this->resolvePpeCode($data['category'], $request->input('ppe_code'));
        $quantity = (int) $data['quantity'];
        $serialSeed = $data['start_serial'];
        $serialWidth = max(strlen($serialSeed), 4);
        $serialStart = (int) ltrim($serialSeed, '0');

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('items', 'public');
        } else {
            $photoPath = $this->defaultPhotos[$data['category']] ?? null;
        }

        DB::beginTransaction();
        try {
            $item = Item::create([
                'name' => $data['name'],
                'category' => $data['category'],
                'total_qty' => 0,
                'available_qty' => 0,
                'photo' => $photoPath,
            ]);

            [$created, $skipped] = $this->createInstances(
                $item,
                $numbers,
                [
                    'year' => $data['year_procured'],
                    'ppe' => $ppeCode,
                    'office' => $data['office_code'],
                ],
                $serialStart,
                $quantity,
                $serialWidth,
                $data['description'] ?? null
            );

            $this->syncItemQuantities($item);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'item_id' => $item->id,
                    'created_count' => count($created),
                    'created_pns' => $created,
                    'skipped_serials' => $skipped,
                ], $skipped ? 207 : 201);
            }

            $message = count($created) ? 'Items saved successfully.' : 'Item saved.';
            if (! empty($skipped)) {
                $message = 'Items saved. Some serials are already in use.';
            }

            return redirect()->route('items.index')->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->hasFile('photo') && $photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            if ($request->wantsJson()) {
                throw $e;
            }

            return redirect()->route('items.index')->with('error', 'Failed to create item: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Item $item, PropertyNumberService $numbers)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'year_procured' => 'required|digits:4',
            'office_code' => 'required|digits:4',
            'serial' => 'required|digits_between:1,8',
            'description' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:2048',
            'item_instance_id' => 'nullable|exists:item_instances,id',
        ]);

        $ppeCode = $this->resolvePpeCode($data['category'], $request->input('ppe_code'));
        $serialWidth = max(strlen($data['serial']), 4);
        $photoPath = $item->photo;
        $uploadedNewPhoto = false;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('items', 'public');
            $uploadedNewPhoto = true;
        } elseif (! $item->photo) {
            $photoPath = $this->defaultPhotos[$data['category']] ?? null;
        }

        DB::beginTransaction();
        try {
            $item->name = $data['name'];
            $item->category = $data['category'];
            $item->photo = $photoPath;
            $item->save();

            $instance = $data['item_instance_id']
                ? ItemInstance::find($data['item_instance_id'])
                : $item->instances()->first();

            if ($instance) {
                $payload = [
                    'year' => $data['year_procured'],
                    'ppe' => $ppeCode,
                    'serial' => str_pad($data['serial'], $serialWidth, '0', STR_PAD_LEFT),
                    'office' => $data['office_code'],
                ];

                $propertyNumber = $numbers->assemble($payload);

                $exists = ItemInstance::where('property_number', $propertyNumber)
                    ->where('id', '!=', $instance->id)
                    ->exists();
                if ($exists) {
                    throw new \RuntimeException('Another item already uses the property number ' . $propertyNumber . '.');
                }

                $instance->property_number = $propertyNumber;
                $instance->year_procured = (int) $data['year_procured'];
                $instance->ppe_code = $ppeCode;
                $instance->serial = $payload['serial'];
                $instance->serial_int = (int) ltrim($payload['serial'], '0');
                $instance->office_code = $data['office_code'];
                if ($data['description'] ?? null) {
                    $instance->notes = $data['description'];
                }
                $instance->save();
            }

            $this->syncItemQuantities($item);

            if ($uploadedNewPhoto && $item->getOriginal('photo') && !str_starts_with($item->getOriginal('photo'), 'defaults/')) {
                Storage::disk('public')->delete($item->getOriginal('photo'));
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Item updated successfully.',
                    'item_id' => $item->id,
                    'property_number' => $instance?->property_number ?? null,
                ]);
            }

            return redirect()->route('items.index')->with('success', 'Item updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($uploadedNewPhoto && $photoPath && !str_starts_with($photoPath, 'defaults/')) {
                Storage::disk('public')->delete($photoPath);
            }

            if ($request->wantsJson()) {
                $status = $e instanceof \RuntimeException ? 409 : 500;
                return response()->json(['message' => $e->getMessage()], $status);
            }

            return redirect()->back()->withInput()->with('error', 'Failed to update item: ' . $e->getMessage());
        }
    }
    protected function createInstances(
        Item $item,
        PropertyNumberService $numbers,
        array $components,
        int $startSerial,
        int $quantity,
        int $serialWidth,
        ?string $notes
    ): array {
        $created = [];
        $skipped = [];

        for ($i = 0; $i < $quantity; $i++) {
            $serialInt = $startSerial + $i;
            $serial = $numbers->padSerial($serialInt, $serialWidth);

            $payload = $components;
            $payload['serial'] = $serial;
            $payload['serial_width'] = $serialWidth;

            $propertyNumber = $numbers->assemble($payload);

            if (ItemInstance::where('property_number', $propertyNumber)->exists()) {
                $skipped[] = $serial;
                continue;
            }

            try {
                ItemInstance::create([
                    'item_id' => $item->id,
                    'property_number' => $propertyNumber,
                    'status' => 'available',
                    'notes' => $notes,
                ]);
                $created[] = $propertyNumber;
            } catch (QueryException $exception) {
                if ($this->isDuplicateKeyException($exception)) {
                    $skipped[] = $serial;
                    continue;
                }

                throw $exception;
            }
        }

        return [$created, $skipped];
    }

    protected function syncItemQuantities(Item $item): void
    {
        $item->total_qty = ItemInstance::where('item_id', $item->id)->count();
        $item->available_qty = ItemInstance::where('item_id', $item->id)
            ->where('status', 'available')
            ->count();
        $item->save();
    }

    protected function isDuplicateKeyException(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        return $sqlState === '23000';
    }
}
























