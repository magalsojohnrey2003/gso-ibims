<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Services\ItemInstanceEventLogger;
use App\Services\PropertyNumberService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ItemController extends Controller
{
    protected string $defaultPhoto = 'images/item.png';

    protected array $categoryCodeMap = [];

    protected array $auditInstanceFields = [
        'property_number',
        'year_procured',
        'category_id',
        'serial',
        'serial_int',
        'office_code',
        'gla',
        'status',
        'notes',
    ];

    public function __construct(private ItemInstanceEventLogger $instanceLogger)
    {
    }

    protected function getCategories(): array
    {
        try {
            $cats = \App\Models\Category::orderBy('name')->get(['id', 'name']);
            return $cats->map(function ($c) {
                return ['id' => $c->id, 'name' => $c->name];
            })->values()->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function resolveCategoryCode(string $category, ?string $fallback = null): string
     {
         if ($fallback && preg_match('/^[A-Z0-9]{1,4}$/', (string) $fallback)) {
             return $fallback;
         }
 
         $normalized = array_change_key_case($this->categoryCodeMap, CASE_LOWER);
         $code = $normalized[strtolower($category)] ?? null;
         if ($code && preg_match('/^[A-Z0-9]{1,4}$/', $code)) {
             $clean = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($code));
             return substr($clean, 0, 4);
         }
 
         $only = preg_replace('/[^A-Za-z0-9]/', '', strtoupper((string) $category));
         if ($only === '') return '';
         return substr($only, 0, 4);
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

        $categoryCodeMap = [];
        try {
            $dbMap = \App\Models\Category::query()->pluck('category_code', 'name')->filter()->toArray();
            foreach ($dbMap as $k => $v) {
                if ($v !== null && $v !== '') {
                    $categoryCodeMap[$k] = strtoupper($v);
                }
            }
        } catch (\Throwable $e) {
            //
        }

        $file = storage_path('app/category_codes.json');
        if (file_exists($file)) {
            $json = json_decode(file_get_contents($file), true);
            if (is_array($json)) {
                foreach ($json as $k => $v) {
                    $categoryCodeMap[$k] = $v;
                }
            }
        }

        $offices = [];

        return view('admin.items.index', compact('items', 'categories', 'categoryCodeMap', 'offices'));
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
                    'category_code' => $instance->category_code ?? null,
                    'gla' => $instance->gla ?? null,
                ];
            });

        return response()->json($instances);
    }

    public function validatePropertyNumbers(Request $request, PropertyNumberService $numbers): JsonResponse
    {
        $data = $request->json()->all() ?: $request->all();

        $pns = $data['property_numbers'] ?? $data['pns'] ?? null;
        if (!is_array($pns)) {
            return response()->json(['message' => 'Invalid payload, property_numbers array expected.'], 422);
        }

        $results = [];
        foreach ($pns as $pn) {
            $pn = (string) $pn;
            try {
                $parsed = $numbers->parse($pn);
                $exists = ItemInstance::where('property_number', $parsed['property_number'])->exists();
                $results[] = [
                    'property_number' => $parsed['property_number'],
                    'valid' => true,
                    'exists' => (bool) $exists,
                ];
            } catch (\InvalidArgumentException $e) {
                $results[] = [
                    'property_number' => $pn,
                    'valid' => false,
                    'reason' => $e->getMessage(),
                    'exists' => false,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'property_number' => $pn,
                    'valid' => false,
                    'reason' => 'Unexpected error',
                    'exists' => false,
                ];
            }
        }

        return response()->json(['valid' => true, 'results' => $results]);
    }

    public function checkSerial(Request $request, PropertyNumberService $numbers)
    {
        $data = $request->validate([
            'year_procured' => 'required|digits:4',
            'office_code' => 'required|alpha_num|min:1|max:4',
            'gla' => 'required|digits_between:1,4',
            'start_serial' => 'required|digits_between:1,8',
            'quantity' => 'required|integer|min:1|max:500',
            'category' => 'nullable|string',
            'category_code' => 'nullable|string|max:20',
            'exclude_instance_id' => 'nullable|integer|exists:item_instances,id',
        ]);

        $category = (string) ($data['category'] ?? '');
        $categoryCodeInput = (string) ($data['category_code'] ?? '');
        $categoryCode = $categoryCodeInput ?: $this->resolveCategoryCode($category, $categoryCodeInput ?: null);

        $serialSeed = $data['start_serial'];
        $serialWidth = max(strlen($serialSeed), 4);
        $serialStartRaw = ltrim($serialSeed, '0');
        $serialStart = $serialStartRaw === '' ? 0 : (int) $serialStartRaw;
        $quantity = (int) $data['quantity'];

        $components = [
            'year' => $data['year_procured'],
            'category' => $categoryCode,
            'gla' => (string) $data['gla'],
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
        // Make the generate inputs optional (not required)
        'year_procured' => 'nullable|digits:4|integer|min:2020|max:' . date('Y'),
        'gla' => 'nullable|digits_between:1,4',
        'office_code' => 'nullable|alpha_num|min:1|max:4',
        'start_serial' => 'nullable|digits_between:1,8',
        'description' => 'nullable|string|max:1000',
        'photo' => 'nullable|image|max:2048',
        // support per-row components (array)
        'property_numbers_components' => 'nullable|array',
        'property_numbers_components.*.year' => 'nullable|digits:4',
        'property_numbers_components.*.category_code' => 'nullable|string',
        'property_numbers_components.*.gla' => 'nullable|digits_between:1,4',
        'property_numbers_components.*.serial' => 'nullable|string',
        'property_numbers_components.*.office' => 'nullable|string|max:4',
    ]);

    $categoryId = (string) ($data['category'] ?? '');
    $categoryCode = $this->resolveCategoryCode($categoryId, $request->input('category_code') ?? null);

    // If per-row components exist, prefer them
    $perRow = $request->input('property_numbers_components', null);

    // If generate bulk inputs are present and sensible, preserve old behavior; otherwise allow creating item without instances
    $hasBulkInputs = !empty($data['year_procured']) && !empty($data['start_serial']) && !empty($data['office_code']);

    $quantity = (int) $data['quantity'];
    $serialSeed = $data['start_serial'] ?? '';
    $serialWidth = max(strlen((string)$serialSeed), 4);
    $serialStartRaw = ltrim((string)$serialSeed, '0');
    $serialStart = $serialStartRaw === '' ? 0 : (int) $serialStartRaw;

    $components = [
        'year' => $data['year_procured'] ?? null,
        'category' => $categoryCode,
        'gla' => isset($data['gla']) ? (string)$data['gla'] : null,
        'office' => $data['office_code'] ?? null,
    ];

    try {
        // If per-row data provided -> validate/prepare property numbers from rows
        $propertyNumbersToCheck = [];
        $rowsToCreate = []; // each row: ['components' => [...], 'notes' => null]

        if (is_array($perRow) && count($perRow) > 0) {
                // Check for duplicate serials within submitted rows
                $serials = array_map(fn($r) => strtoupper($r['payload']['serial']), $rowsToCreate);
                $duplicates = array_unique(array_diff_assoc($serials, array_unique($serials)));
                if (!empty($duplicates)) {
                    $dupList = implode(', ', $duplicates);
                    if ($request->wantsJson() || $request->isXmlHttpRequest()) {
                        return response()->json([
                            'message' => 'Duplicate serial numbers detected in the input rows.',
                            'duplicates' => $duplicates,
                        ], 422);
                    }
                    return redirect()->back()->withInput()->with('error', 'Duplicate serial numbers detected in the input rows: ' . $dupList);
                }

            foreach ($perRow as $idx => $row) {
                // normalize keys (some inputs may come as property_numbers_components[1][year], etc.)
                $year = isset($row['year']) ? (string)$row['year'] : null;
                $cat = isset($row['category_code']) ? (string)$row['category_code'] : null;
                $gla = isset($row['gla']) ? (string)$row['gla'] : null;
                $serial = isset($row['serial']) ? (string)$row['serial'] : null;
                $office = isset($row['office']) ? (string)$row['office'] : null;

                // Basic server-side sanity check (rows are expected to be complete per requirements)
                if (empty($year) || empty($cat) || empty($gla) || empty($serial) || empty($office)) {
                    // invalid row -> abort with 422 and minimal message (client side should prevent this)
                    if ($request->wantsJson() || $request->isXmlHttpRequest()) {
                        return response()->json(['message' => 'Each property number row must include year, category, gla, serial and office.'], 422);
                    }
                    return redirect()->back()->withInput()->with('error', 'Each property number row must include year, category, gla, serial and office.');
                }

                // Normalize category code to uppercase and strip non-alnum (PropertyNumberService expects uppercase alnum)
                $catNorm = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $cat));
                $payload = [
                    'year' => $year,
                    'category' => $catNorm,
                    'gla' => (string)$gla,
                    'serial' => (string)$serial,
                    'serial_width' => max(strlen((string)$serial), 4),
                    'office' => (string)$office,
                ];

                // Attempt to assemble canonical PN (this may throw if components invalid)
                $pn = $numbers->assemble($payload);
                $propertyNumbersToCheck[] = $pn;
                $rowsToCreate[] = ['payload' => $payload, 'pn' => $pn];
            }
        } elseif ($hasBulkInputs) {
            // Existing bulk flow: compute expected property numbers
            for ($i = 0; $i < $quantity; $i++) {
                $serialInt = $serialStart + $i;
                $serial = $numbers->padSerial($serialInt, $serialWidth);
                $payload = $components + ['serial' => $serial, 'serial_width' => $serialWidth];
                $pn = $numbers->assemble($payload);
                $propertyNumbersToCheck[] = $pn;
            }
        } else {
            // No rows and no bulk inputs: create item only (no instances)
            $propertyNumbersToCheck = [];
        }

        // Check duplicates among existing instances
        if (!empty($propertyNumbersToCheck)) {
            $existing = ItemInstance::query()
                ->whereIn('property_number', $propertyNumbersToCheck)
                ->pluck('property_number')
                ->all();

            if (!empty($existing)) {
                $conflictSerials = [];
                foreach ($existing as $exPn) {
                    try {
                        $conflictSerials[] = $numbers->parse($exPn)['serial'] ?? $exPn;
                    } catch (\Throwable $e) {
                        $conflictSerials[] = $exPn;
                    }
                }

                if ($request->wantsJson() || $request->isXmlHttpRequest()) {
                    return response()->json([
                        'message' => 'This Property Number is already taken.',
                        'conflicts' => array_values(array_unique($conflictSerials)),
                        'existing_property_numbers' => array_values(array_unique($existing)),
                    ], 409);
                }

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'This Property Number is already taken.');
            }
        }

    } catch (\Throwable $e) {
        if ($request->wantsJson() || $request->isXmlHttpRequest()) {
            return response()->json(['message' => 'Unable to validate property numbers: ' . $e->getMessage()], 422);
        }
        return redirect()->back()->withInput()->with('error', 'Unable to validate property numbers: ' . $e->getMessage());
    }

    $photoPath = null;
    if ($request->hasFile('photo')) {
        $photoPath = $request->file('photo')->store('items', 'public');
    } else {
        $photoPath = $this->defaultPhoto;
    }

    DB::beginTransaction();
    try {
        $item = Item::create([
            'name' => $data['name'],
            'category' => (string) $data['category'],
            'total_qty' => 0,
            'available_qty' => 0,
            'photo' => $photoPath,
        ]);

        $created = [];
        $skipped = [];

        // Create instances either from rowsToCreate or using bulk generation
        if (!empty($rowsToCreate)) {
            foreach ($rowsToCreate as $r) {
                $payload = $r['payload'];
                $propertyNumber = $r['pn'];
                if (ItemInstance::where('property_number', $propertyNumber)->exists()) {
                    $skipped[] = $payload['serial'];
                    continue;
                }
                try {
                    $instance = ItemInstance::create([
                        'item_id' => $item->id,
                        'property_number' => $propertyNumber,
                        'year_procured' => (int) $payload['year'],
                        'category_code' => $payload['category'] ?? null,
                        'gla' => isset($payload['gla']) ? (string) $payload['gla'] : null,
                        'serial' => (string) $payload['serial'],
                        'serial_int' => is_numeric($payload['serial']) ? (int) ltrim($payload['serial'], '0') : null,
                        'office_code' => $payload['office'] ?? null,
                        'status' => 'available',
                        'notes' => $data['description'] ?? null,
                    ]);
                    $created[] = $propertyNumber;

                    $this->instanceLogger->log(
                        $instance,
                        'created',
                        [
                            'property_number' => $propertyNumber,
                            'serial' => $payload['serial'],
                            'serial_int' => is_numeric($payload['serial']) ? (int) ltrim($payload['serial'], '0') : null,
                            'notes' => $data['description'] ?? null,
                            'context' => ['source' => 'admin_item_store'],
                        ],
                        $request->user()
                    );
                } catch (QueryException $exception) {
                    if ($this->isDuplicateKeyException($exception)) {
                        $skipped[] = $payload['serial'];
                        continue;
                    }
                    throw $exception;
                }
            }
        } elseif ($hasBulkInputs) {
            // original bulk creation using createInstances helper
            [$created, $skipped] = $this->createInstances(
                $item,
                $numbers,
                [
                    'year' => $data['year_procured'],
                    'category' => $categoryCode,
                    'gla' => (string) ($data['gla'] ?? ''),
                    'office' => $data['office_code'],
                ],
                $serialStart,
                $quantity,
                $serialWidth,
                $data['description'] ?? null,
                $request->user(),
                ['source' => 'admin_item_store']
            );
        } else {
            // no instances to create
        }

        $this->syncItemQuantities($item);

        DB::commit();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => (count($created) > 1) ? 'Items created.' : (count($created) === 1 ? 'Item created.' : 'Item created.'),
                'item_id' => $item->id,
                'created_count' => count($created),
                'created_pns' => $created,
                'skipped_serials' => $skipped,
                'item' => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category,
                    'total_qty' => $item->total_qty,
                    'available_qty' => $item->available_qty,
                ],
            ], $skipped ? 207 : 201);
        }

        if (! empty($skipped)) {
            $message = (count($created) > 1)
                ? 'Items created. Some serials skipped.'
                : (count($created) === 1 ? 'Item created. Some serials skipped.' : 'Item created. Some serials skipped.');
        } else {
            $message = (count($created) > 1) ? 'Items created.' : (count($created) === 1 ? 'Item created.' : 'Item created.');
        }

        return redirect()->route('items.index')->with('success', $message);

    } catch (\Throwable $e) {
        DB::rollBack();
        if ($request->hasFile('photo') && $photoPath) {
            Storage::disk('public')->delete($photoPath);
        }

        if ($request->wantsJson() || $request->isXmlHttpRequest()) {
            $status = $e instanceof \RuntimeException ? 409 : 500;
            return response()->json(['message' => $e->getMessage() ?: 'Failed to create item.'], $status);
        }
        return redirect()->route('items.index')->with('error', 'Failed to create item: ' . $e->getMessage());
    }
}


public function update(Request $request, Item $item, PropertyNumberService $numbers)
{
    $data = $request->validate([
        'name' => 'required|string|max:255',
        'category' => 'required|string',
        'year_procured' => 'required|digits:4|integer|min:2020|max:' . date('Y'),
        'gla' => 'nullable|digits_between:1,4',
        'office_code' => 'required|alpha_num|min:1|max:4',
        'serial' => 'required|digits_between:1,8',
        'description' => 'nullable|string|max:1000',
        'photo' => 'nullable|image|max:2048',
        'item_instance_id' => 'nullable|exists:item_instances,id',
    ]);

    $categoryCode = $this->resolveCategoryCode($data['category'], $request->input('category_code'));
    $serialWidth = max(strlen($data['serial']), 4);
    $photoPath = $item->photo;
    $uploadedNewPhoto = false;

    if ($request->hasFile('photo')) {
        $photoPath = $request->file('photo')->store('items', 'public');
        $uploadedNewPhoto = true;
    } elseif (! $item->photo) {
        $photoPath = $this->defaultPhoto;
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
            $trackedKeys = array_flip($this->auditInstanceFields);
            $before = array_intersect_key($instance->getOriginal(), $trackedKeys);

            $payload = [
                'year' => $data['year_procured'],
                'category' => $categoryCode,
                'gla' => isset($data['gla']) ? (string) $data['gla'] : ($instance->gla ?? null),
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
            $instance->category_code = $categoryCode;
            $instance->gla = isset($payload['gla']) ? (string) $payload['gla'] : null;
            $instance->serial = $payload['serial'];
            $instance->serial_int = (int) ltrim($payload['serial'], '0');
            $instance->office_code = $data['office_code'];
            if (! empty($data['description'])) {
                $instance->notes = $data['description'];
            }
            $instance->save();

            $after = array_intersect_key($instance->getAttributes(), $trackedKeys);
            $changes = $this->diffInstanceChanges($before, $after);

            if (! empty($changes)) {
                $this->instanceLogger->log(
                    $instance,
                    'updated',
                    [
                        'changes' => $changes,
                        'source' => 'admin_item_update',
                    ],
                    $request->user()
                );
            }
        }

        $this->syncItemQuantities($item);

        if ($uploadedNewPhoto && $item->getOriginal('photo') && ! str_starts_with($item->getOriginal('photo'), 'defaults/')) {
            Storage::disk('public')->delete($item->getOriginal('photo'));
        }

        DB::commit();

        if ($request->wantsJson()) {
            $photoUrl = $item->photo ? asset('storage/' . ltrim($item->photo, '/')) : null;
            return response()->json([
                'message' => 'Item updated.',
                'item_id' => $item->id,
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'total_qty' => $item->total_qty,
                'available_qty' => $item->available_qty,
                'property_number' => $instance?->property_number ?? null,
                'office_code' => $instance?->office_code ?? null,
                'year_procured' => $instance?->year_procured ?? null,
                'serial' => $instance?->serial ?? null,
                'gla' => $instance?->gla ?? null,
                'category_code' => $instance?->category_code ?? null,
                'item_instance_id' => $instance?->id ?? null,
                'photo' => $photoUrl,
                'item' => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category,
                    'total_qty' => $item->total_qty,
                    'available_qty' => $item->available_qty,
                    'photo' => $photoUrl,
                ],
            ]);
        }

        return redirect()->route('items.index')->with('success', 'Item updated.');

    } catch (\Throwable $e) {
        DB::rollBack();

        if ($uploadedNewPhoto && $photoPath && ! str_starts_with($photoPath, 'defaults/')) {
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
        ?string $notes,
        ?Authenticatable $actor = null,
        array $context = []
    ): array {
        $created = [];
        $skipped = [];
        $context = array_merge(['source' => 'admin_item_store'], $context);

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
                $instance = ItemInstance::create([
                    'item_id' => $item->id,
                    'property_number' => $propertyNumber,
                    'year_procured' => (int) $payload['year'],
                    'category_code' => $payload['category'] ?? null,
                    'gla' => isset($payload['gla']) ? (string) $payload['gla'] : null,
                    'serial' => $serial,
                    'serial_int' => $serialInt,
                    'office_code' => $payload['office'] ?? null,
                    'status' => 'available',
                    'notes' => $notes,
                ]);
                $created[] = $propertyNumber;

                $this->instanceLogger->log(
                    $instance,
                    'created',
                    [
                        'property_number' => $propertyNumber,
                        'serial' => $serial,
                        'serial_int' => $serialInt,
                        'notes' => $notes,
                        'context' => $context,
                    ],
                    $actor
                );
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

    protected function diffInstanceChanges(array $before, array $after): array
    {
        $changes = [];

        foreach ($this->auditInstanceFields as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;

            if ($old !== $new) {
                $changes[$field] = [
                    'old' => $old,
                    'new' => $new,
                ];
            }
        }

        return $changes;
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $item = Item::with('instances')->findOrFail($id);

            if (isset($item->total_qty, $item->available_qty) && $item->available_qty < $item->total_qty) {
                DB::rollBack();
                return redirect()->route('items.index')
                                ->with('error', 'Cannot delete item: some units are borrowed.');
            }

            $activeStatuses = ['approved', 'borrowed', 'issued', 'checked_out'];

            foreach ($item->instances as $instance) {
                if (method_exists($instance, 'borrowRecords')) {
                    if ($instance->borrowRecords()->whereHas('borrowRequest', function ($q) use ($activeStatuses) {
                        $q->whereIn('status', $activeStatuses);
                    })->exists()) {
                        DB::rollBack();
                        return redirect()->route('items.index')
                                        ->with('error', 'Cannot delete item: some units are borrowed.');
                    }
                }

                if (isset($instance->is_borrowed) && $instance->is_borrowed) {
                    DB::rollBack();
                    return redirect()->route('items.index')
                                    ->with('error', 'Cannot delete item: some units are borrowed.');
                }

                if (isset($instance->status) && in_array(strtolower($instance->status), $activeStatuses, true)) {
                    DB::rollBack();
                    return redirect()->route('items.index')
                                    ->with('error', 'Cannot delete item: some units are borrowed.');
                }
            }

            if (!empty($item->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($item->photo);
            }

            $item->instances()->delete();

            $item->delete();

            DB::commit();

            return redirect()->route('items.index')->with('success', 'Item deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('items.index')->with('error', 'Failed to delete item: ' . $e->getMessage());
        }
    }


}