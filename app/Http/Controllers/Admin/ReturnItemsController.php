<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BorrowItemInstance;
use App\Models\BorrowRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\ItemInstanceEventLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ReturnItemsController extends Controller
{
    public function __construct(private ItemInstanceEventLogger $eventLogger)
    {
    }

    public function index()
    {
        return view('admin.return-items.index');
    }

    public function list()
    {
        // Get regular borrow requests
        $borrowRequests = BorrowRequest::with(['user', 'borrowedInstances'])
            // Include delivered so requests remain visible for return processing after delivery
                ->whereIn('delivery_status', ['dispatched', 'delivered', 'returned', 'return_pending'])
            ->orderByDesc('dispatched_at')
            ->get()
            ->map(function (BorrowRequest $request) {
                $conditionKey = $this->summarizeCondition($request);
                    $proofUrl = $request->return_proof_path ? Storage::disk('public')->url($request->return_proof_path) : null;

                return [
                    'id' => $request->id,
                    'borrow_request_id' => $request->id,
                    'walk_in_request_id' => null,
                    'request_type' => 'regular',
                    'formatted_request_id' => $request->formatted_request_id,
                    'borrower_name' => $this->formatBorrowerName($request),
                    'status' => $request->status ?? 'pending',
                    'delivery_status' => $request->delivery_status ?? 'pending',
                    'condition' => $conditionKey,
                    'condition_label' => $this->formatConditionLabel($conditionKey),
                    'borrow_date' => $request->borrow_date?->toDateString(),
                    'return_date' => $request->return_date?->toDateString(),
                    'return_timestamp' => $request->delivered_at?->toDateTimeString(),
                        'return_proof_path' => $request->return_proof_path,
                        'return_proof_url' => $proofUrl,
                        'return_proof_notes' => $request->return_proof_notes,
                ];
            });

        // Get walk-in requests that have been delivered
        $walkInRequests = \App\Models\WalkInRequest::with('items')
            ->whereIn('status', ['delivered', 'returned'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($walkIn) {
                // Get the condition from borrow instances
                $instances = BorrowItemInstance::where('walk_in_request_id', $walkIn->id)->get();
                $conditionKey = $this->summarizeConditionForInstances($instances);

                // Determine delivery_status based on walk-in status
                $deliveryStatus = $walkIn->status === 'returned' ? 'returned' : 'delivered';

                return [
                    'id' => 'W' . $walkIn->id, // Prefix with W to distinguish from regular requests
                    'borrow_request_id' => null,
                    'walk_in_request_id' => $walkIn->id,
                    'request_type' => 'walk-in',
                    'formatted_request_id' => $walkIn->formatted_request_id,
                    'borrower_name' => $walkIn->borrower_name ?? 'Walk-in Borrower',
                    'status' => $walkIn->status,
                    'delivery_status' => $deliveryStatus,
                    'condition' => $conditionKey,
                    'condition_label' => $this->formatConditionLabel($conditionKey),
                    'borrow_date' => $walkIn->borrowed_at?->toDateString(),
                    'return_date' => $walkIn->returned_at?->toDateString(),
                    'return_timestamp' => $walkIn->updated_at?->toDateTimeString(),
                    'return_proof_path' => null,
                    'return_proof_url' => null,
                    'return_proof_notes' => null,
                ];
            });

        // Merge both collections
        $requests = $borrowRequests->concat($walkInRequests)->sortByDesc('return_timestamp')->values();

        return response()->json($requests);
    }

    public function show(BorrowRequest $borrowRequest)
    {
        $borrowRequest->load([
            'user',
            'borrowedInstances.instance',
            'borrowedInstances.item',
            'items.item',
        ]);

        $borrowedInstances = $borrowRequest->borrowedInstances;
        $items = $this->formatBorrowInstances($borrowedInstances);
        $itemOptions = $this->buildItemOptionsFromItems($items);

        $requestItems = $borrowRequest->items->map(function ($ri) {
            return [
                'id' => $ri->id,
                'item_id' => $ri->item_id,
                'name' => $ri->item?->name ?? 'Item',
                'approved_quantity' => $ri->quantity,
                'quantity' => $ri->quantity,
                'received_quantity' => $ri->received_quantity,
            ];
        })->values();

        return response()->json([
            'id' => $borrowRequest->id,
            'borrow_request_id' => $borrowRequest->id,
            'formatted_request_id' => $borrowRequest->formatted_request_id,
            'borrower' => $this->formatBorrowerName($borrowRequest),
            'address' => $borrowRequest->location ?? '',
            'status' => $borrowRequest->status ?? 'pending',
            'delivery_status' => $borrowRequest->delivery_status ?? 'pending',
            'borrow_date' => $borrowRequest->borrow_date?->toDateString(),
            'return_date' => $borrowRequest->return_date?->toDateString(),
            'return_timestamp' => $borrowRequest->delivered_at?->toDateTimeString(),
                'return_proof_path' => $borrowRequest->return_proof_path,
                'return_proof_url' => $borrowRequest->return_proof_path ? Storage::disk('public')->url($borrowRequest->return_proof_path) : null,
                'return_proof_notes' => $borrowRequest->return_proof_notes,
            'items' => $items,
                'request_items' => $requestItems,
            'item_options' => $itemOptions,
            'default_item' => $itemOptions[0]['name'] ?? null,
            'condition_summary' => $this->formatConditionLabel($this->summarizeCondition($borrowRequest)),
        ]);
    }

    public function collect(Request $request, BorrowRequest $borrowRequest)
    {
        // Allow collection for both dispatched and delivered requests
        if (! in_array($borrowRequest->delivery_status, ['dispatched', 'delivered'], true)) {
            return response()->json(['message' => 'Only dispatched or delivered requests can be marked as collected.'], 422);
        }

        DB::beginTransaction();
        try {
            $borrowRequest->load([
                'borrowedInstances.instance',
                'borrowedInstances.item',
                'items.item',
            ]);
            $borrowRequest->loadMissing('user');

            // Build a map of item_id => (approved - received) to track shortages
            $shortageMap = [];
            foreach ($borrowRequest->items as $requestItem) {
                $approved = (int) ($requestItem->quantity ?? 0);
                $received = (int) ($requestItem->received_quantity ?? $approved);
                $shortage = max(0, $approved - $received);
                if ($shortage > 0) {
                    $itemId = $requestItem->item_id;
                    $shortageMap[$itemId] = ($shortageMap[$itemId] ?? 0) + $shortage;
                }
            }

            // Group instances by item_id for shortage allocation
            $instancesByItem = $borrowRequest->borrowedInstances->groupBy('item_id');

            foreach ($borrowRequest->borrowedInstances as $instance) {
                $previousCondition = $instance->return_condition ?? 'pending';

                if (! $instance->returned_at) {
                    $instance->returned_at = now();
                }

                $instance->condition_updated_at = now();

                // Check if this item has a shortage and mark accordingly
                $itemId = $instance->item_id;
                if (isset($shortageMap[$itemId]) && $shortageMap[$itemId] > 0) {
                    $instance->return_condition = 'not_received';
                    $shortageMap[$itemId]--;
                } else {
                    $instance->return_condition = 'good';
                }

                $instance->save();

                $instance->loadMissing(['item', 'instance']);
                $this->syncInventoryForConditionChange($instance, $previousCondition, $instance->return_condition);

                $this->logConditionEvent($instance, 'returned', $request->user(), [
                    'trigger' => 'admin_collect',
                ]);
            }

            $borrowRequest->status = 'returned';
            $borrowRequest->delivery_status = 'returned';
            if (! $borrowRequest->delivered_at) {
                $borrowRequest->delivered_at = now();
            }
            $borrowRequest->save();

            $borrowRequest->load('borrowedInstances.instance', 'borrowedInstances.item');

            $this->syncBorrowerBorrowingStatus($borrowRequest->user);

            DB::commit();

            $instancesPayload = $this->formatBorrowInstances($borrowRequest->borrowedInstances);

            return response()->json([
                'message' => 'Items marked as returned successfully.',
                'status' => $borrowRequest->status,
                'delivery_status' => $borrowRequest->delivery_status,
                'borrow_request_id' => $borrowRequest->id,
                'return_timestamp' => $borrowRequest->delivered_at?->toDateTimeString(),
                'condition_summary' => $this->formatConditionLabel($this->summarizeCondition($borrowRequest)),
                'instances' => $instancesPayload,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to mark as collected.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showWalkIn($id)
    {
        $walkInRequest = \App\Models\WalkInRequest::findOrFail($id);

        $walkInRequest->loadMissing('items.item');

        $instances = BorrowItemInstance::with(['instance', 'item'])
            ->where('walk_in_request_id', $id)
            ->get();

        $items = $this->formatBorrowInstances($instances);
        $itemOptions = $this->buildItemOptionsFromItems($items);

        $requestItems = $walkInRequest->items->map(function ($ri) {
            return [
                'id' => $ri->id,
                'item_id' => $ri->item_id,
                'name' => $ri->item?->name ?? 'Item',
                'approved_quantity' => $ri->quantity,
                'quantity' => $ri->quantity,
                'received_quantity' => $ri->received_quantity,
            ];
        })->values();

        return response()->json([
            'id' => 'W' . $id,
            'walk_in_request_id' => $id,
            'request_type' => 'walk-in',
            'formatted_request_id' => $walkInRequest->formatted_request_id,
            'borrower' => $walkInRequest->borrower_name ?? 'Walk-in Borrower',
            'address' => $walkInRequest->office_agency ?? '',
            'status' => 'delivered',
            'delivery_status' => 'delivered',
            'borrow_date' => $walkInRequest->borrowed_at?->toDateString(),
            'return_date' => $walkInRequest->returned_at?->toDateString(),
            'return_timestamp' => $walkInRequest->updated_at?->toDateTimeString(),
            'items' => $items,
            'request_items' => $requestItems,
            'item_options' => $itemOptions,
            'default_item' => $itemOptions[0]['name'] ?? null,
            'condition_summary' => $this->formatConditionLabel($this->summarizeConditionForInstances($instances)),
        ]);
    }

    public function collectWalkIn(Request $request, $id)
    {
        $walkInRequest = \App\Models\WalkInRequest::findOrFail($id);

        DB::beginTransaction();
        try {
            $walkInRequest->loadMissing('items.item');

            $instances = BorrowItemInstance::with(['instance', 'item'])
                ->where('walk_in_request_id', $id)
                ->get();

            // Build a map of item_id => (approved - received) to track shortages
            $shortageMap = [];
            foreach ($walkInRequest->items as $requestItem) {
                $approved = (int) ($requestItem->quantity ?? 0);
                $received = (int) ($requestItem->received_quantity ?? $approved);
                $shortage = max(0, $approved - $received);
                if ($shortage > 0) {
                    $itemId = $requestItem->item_id;
                    $shortageMap[$itemId] = ($shortageMap[$itemId] ?? 0) + $shortage;
                }
            }

            foreach ($instances as $instance) {
                $previousCondition = $instance->return_condition ?? 'pending';

                if (! $instance->returned_at) {
                    $instance->returned_at = now();
                }

                $instance->condition_updated_at = now();

                // Check if this item has a shortage and mark accordingly
                $itemId = $instance->item_id;
                if (isset($shortageMap[$itemId]) && $shortageMap[$itemId] > 0) {
                    $instance->return_condition = 'not_received';
                    $shortageMap[$itemId]--;
                } else {
                    $instance->return_condition = 'good';
                }

                $instance->save();

                // Sync inventory based on condition change
                $this->syncInventoryForConditionChange($instance, $previousCondition, $instance->return_condition);

                $this->logConditionEvent($instance, 'returned', $request->user(), [
                    'trigger' => 'walkin_collect',
                ]);
            }

            // Update walk-in request status to 'returned'
            $walkInRequest->status = 'returned';
            $walkInRequest->delivery_status = 'returned';
            $walkInRequest->returned_at = now();
            $walkInRequest->save();

            DB::commit();

            $instancesPayload = $this->formatBorrowInstances($instances);

            return response()->json([
                'message' => 'Walk-in items marked as returned successfully.',
                'walk_in_request_id' => $id,
                'return_timestamp' => now()->toDateTimeString(),
                'condition_summary' => $this->formatConditionLabel($this->summarizeConditionForInstances($instances)),
                'instances' => $instancesPayload,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Failed to mark walk-in items as collected', [
                'walk_in_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to mark walk-in items as collected.',
                'error' => $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    public function updateInstance(Request $request, BorrowItemInstance $borrowItemInstance)
    {
        $data = $request->validate([
            'condition' => 'required|in:good,missing,damage,minor_damage,not_received',
        ]);

        $newCondition = $data['condition'];
        $previousCondition = $borrowItemInstance->return_condition ?? 'pending';

        $borrowItemInstance->loadMissing('instance');

        $latestIds = $this->resolveLatestReturnIds([
            $borrowItemInstance->item_instance_id,
        ]);

        $itemInstanceId = $borrowItemInstance->item_instance_id;
        if ($itemInstanceId && ($latestIds[$itemInstanceId] ?? $borrowItemInstance->id) !== $borrowItemInstance->id) {
            return response()->json([
                'message' => 'Only the most recent return record can be modified.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $borrowItemInstance->return_condition = $newCondition;
            if (! $borrowItemInstance->returned_at) {
                $borrowItemInstance->returned_at = now();
            }
            $borrowItemInstance->condition_updated_at = now();
            $borrowItemInstance->save();

            $this->syncInventoryForConditionChange($borrowItemInstance, $previousCondition, $newCondition);

            $borrowRequest = $borrowItemInstance->borrowRequest()->with('borrowedInstances')->first();
            if ($borrowRequest) {
                $borrowRequest->loadMissing('user');
                $this->maybeMarkReturned($borrowRequest);
                $this->syncBorrowerBorrowingStatus($borrowRequest->user);
            }

            $this->logConditionEvent($borrowItemInstance, 'condition_updated', $request->user(), [
                'trigger' => 'admin_condition_update',
            ]);

            DB::commit();

            $descriptor = $this->describeBorrowInstance(
                $borrowItemInstance,
                $this->resolveLatestReturnIds([$borrowItemInstance->item_instance_id])
            );

            return response()->json(array_merge(
                [
                    'message' => 'Condition updated successfully.',
                    'available_qty' => optional($borrowItemInstance->item)->available_qty,
                    'borrow_summary' => $borrowRequest
                        ? $this->formatConditionLabel($this->summarizeCondition($borrowRequest))
                        : null,
                    'latest_status' => $borrowRequest?->status,
                    'latest_delivery_status' => $borrowRequest?->delivery_status,
                ],
                $descriptor,
                [
                    'item_instance_id' => $borrowItemInstance->item_instance_id,
                    'item_id' => $borrowItemInstance->item_id,
                ]
            ));
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update condition.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function formatBorrowInstances(EloquentCollection|array $instances): array
    {
        if ($instances instanceof EloquentCollection) {
            $instances->loadMissing('item', 'instance');
            $collection = $instances;
        } else {
            $collection = collect($instances);
            $collection->each(fn (BorrowItemInstance $instance) => $instance->loadMissing('item', 'instance'));
        }

        if ($collection->isEmpty()) {
            return [];
        }

        $latestIds = $this->resolveLatestReturnIds(
            $collection
                ->pluck('item_instance_id')
                ->filter()
                ->unique()
                ->all()
        );

        return $collection
            ->map(fn (BorrowItemInstance $instance) => $this->describeBorrowInstance($instance, $latestIds))
            ->values()
            ->all();
    }

    private function syncBorrowerBorrowingStatus(?User $user): void
    {
        if (! $user) {
            return;
        }

        $baseQuery = BorrowItemInstance::query()
            ->whereHas('borrowRequest', function ($query) use ($user) {
                $query->where('user_id', $user->getKey());
            })
            ->whereNotNull('return_condition');

        $riskConditions = ['missing', 'damage', 'damaged', 'not_received'];
        $fairConditions = ['minor_damage'];

        $riskExists = (clone $baseQuery)
            ->whereIn(DB::raw('LOWER(return_condition)'), $riskConditions)
            ->exists();

        $status = 'good';

        if ($riskExists) {
            $status = 'risk';
        } else {
            $fairExists = (clone $baseQuery)
                ->whereIn(DB::raw('LOWER(return_condition)'), $fairConditions)
                ->exists();

            if ($fairExists) {
                $status = 'fair';
            }
        }

        if ($user->borrowing_status !== $status) {
            $user->borrowing_status = $status;
            $user->save();
        }
    }

    private function buildItemOptionsFromItems(array $items): array
    {
        return collect($items)
            ->groupBy(fn ($item) => $item['item_name'] ?? 'Unknown Item')
            ->map(fn ($group, $name) => ['name' => $name, 'count' => $group->count()])
            ->values()
            ->all();
    }

    private function describeBorrowInstance(BorrowItemInstance $instance, array $latestIds = []): array
    {
        $instance->loadMissing('item', 'instance');

        $label = $instance->instance?->property_number
            ?? $instance->instance?->serial
            ?? ($instance->item?->name ?? 'Untracked Item');

        $condition = $instance->return_condition ?? 'pending';
        $inventoryStatus = strtolower($instance->instance?->status ?? 'unknown');
        if ($condition === 'not_received') {
            $inventoryStatus = 'not_received';
        }
        $itemInstanceId = $instance->item_instance_id;
        $latestId = $itemInstanceId ? ($latestIds[$itemInstanceId] ?? null) : null;
        $isLatestRecord = $latestId === null || $latestId === $instance->id;

        $lockReason = null;
        if (! $isLatestRecord) {
            $lockReason = 'This return record is read-only because a newer return update exists.';
        }

        return [
            'id' => $instance->id,
            'borrow_item_instance_id' => $instance->id,
            'item_instance_id' => $instance->item_instance_id,
            'item_id' => $instance->item_id,
            'property_label' => $label,
            'item_name' => $instance->item?->name ?? 'Unknown Item',
            'condition' => $condition,
            'condition_label' => $this->formatConditionLabel($condition),
            'returned_at' => $instance->returned_at?->toDateTimeString(),
            'inventory_status' => $inventoryStatus,
            'status' => $instance->instance?->status ?? 'unknown',
            'inventory_status_label' => $this->formatInventoryStatus($instance->instance?->status),
            'can_update' => $isLatestRecord,
            'lock_reason' => $lockReason,
            'is_latest_record' => $isLatestRecord,
        ];
    }

    private function resolveLatestReturnIds(array $itemInstanceIds): array
    {
        $ids = array_filter(array_unique(array_filter($itemInstanceIds)));
        if (empty($ids)) {
            return [];
        }

        $ordered = BorrowItemInstance::query()
            ->select('item_instance_id', 'id', 'condition_updated_at', 'returned_at', 'created_at')
            ->whereIn('item_instance_id', $ids)
            ->orderBy('item_instance_id')
            ->orderByDesc('condition_updated_at')
            ->orderByDesc('returned_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $latest = [];
        foreach ($ordered as $instance) {
            $key = $instance->item_instance_id;
            if (! isset($latest[$key])) {
                $latest[$key] = $instance->id;
            }
        }

        return $latest;
    }

    private function syncInventoryForConditionChange(BorrowItemInstance $instance, string $previous, string $current): void
    {
        $item = $instance->item;
        $itemInstance = $instance->instance;

        if ($itemInstance) {
            $itemInstance->status = $this->mapConditionToInstanceStatus($current);
            $itemInstance->save();
        }

        if (! $item) {
            return;
        }

        $previousAddsStock = $this->addsBackToStock($previous);
        $currentAddsStock = $this->addsBackToStock($current);

        if ($previousAddsStock === $currentAddsStock) {
            return;
        }

        $available = (int) $item->available_qty;
        if ($previousAddsStock && ! $currentAddsStock) {
            $item->available_qty = max(0, $available - 1);
        } elseif (! $previousAddsStock && $currentAddsStock) {
            $total = $item->total_qty ? (int) $item->total_qty : null;
            $candidate = $available + 1;
            $item->available_qty = $total !== null ? min($candidate, $total) : $candidate;
        }

        $item->save();
    }

    private function maybeMarkReturned(BorrowRequest $borrowRequest): void
    {
        $pending = $borrowRequest->borrowedInstances
            ->contains(fn (BorrowItemInstance $instance) => ($instance->return_condition ?? 'pending') === 'pending');

        if (! $pending) {
            $borrowRequest->status = 'returned';
            $borrowRequest->delivery_status = 'returned';
            if (! $borrowRequest->delivered_at) {
                $borrowRequest->delivered_at = now();
            }
            $borrowRequest->save();
        }
    }

    private function summarizeCondition(BorrowRequest $borrowRequest): string
    {
        $instances = $borrowRequest->borrowedInstances;
        if ($instances->isEmpty()) {
            return 'pending';
        }

        $conditions = $instances->pluck('return_condition')->map(fn ($value) => $value ?: 'pending');

        if ($conditions->contains('not_received')) {
            return 'not_received';
        }

        if ($conditions->contains('missing')) {
            return 'missing';
        }

        if ($conditions->contains('damage')) {
            return 'damage';
        }

        if ($conditions->contains('minor_damage')) {
            return 'minor_damage';
        }

        if ($conditions->every(fn ($condition) => $condition === 'good')) {
            return 'good';
        }

        return 'pending';
    }

    private function summarizeConditionForInstances($instances): string
    {
        if ($instances->isEmpty()) {
            return 'pending';
        }

        $conditions = $instances->pluck('return_condition')->map(fn ($value) => $value ?: 'pending');

        if ($conditions->contains('not_received')) {
            return 'not_received';
        }

        if ($conditions->contains('missing')) {
            return 'missing';
        }

        if ($conditions->contains('damage')) {
            return 'damage';
        }

        if ($conditions->contains('minor_damage')) {
            return 'minor_damage';
        }

        if ($conditions->every(fn ($condition) => $condition === 'good')) {
            return 'good';
        }

        return 'pending';
    }

    private function formatConditionLabel(string $condition): string
    {
        return match ($condition) {
            'good' => 'Good',
            'missing' => 'Missing',
            'damage' => 'Damage',
            'minor_damage' => 'Minor Damage',
            'not_received' => 'Not Received',
            default => 'Pending',
        };
    }

    private function formatInventoryStatus(?string $status): string
    {
        return match (strtolower($status ?? '')) {
            'available' => 'Available',
            'borrowed' => 'Borrowed',
            'damaged' => 'Damaged',
            'under_repair' => 'Under Repair',
            'retired' => 'Retired',
            'missing' => 'Missing',
            'not_received' => 'Not Received',
            default => 'Unknown',
        };
    }

    private function mapConditionToInstanceStatus(string $condition): string
    {
        return match ($condition) {
            'good' => 'available',
            'minor_damage' => 'damaged',
            'damage' => 'damaged',
            'missing' => 'missing',
            // Persist as missing to align with existing inventory status values
            'not_received' => 'missing',
            default => 'borrowed',
        };
    }

    private function addsBackToStock(string $condition): bool
    {
        return $condition === 'good';
    }

    private function formatBorrowerName(BorrowRequest $request): string
    {
        $user = $request->user;
        if (! $user) {
            return 'Unknown';
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        return $name !== '' ? $name : 'Unknown';
    }

    private function logConditionEvent(BorrowItemInstance $borrowItemInstance, string $action, ?Authenticatable $actor = null, array $extra = []): void
    {
        $borrowItemInstance->loadMissing('instance', 'item', 'borrowRequest.user', 'walkInRequest');

        $itemInstance = $borrowItemInstance->instance;
        if (! $itemInstance) {
            return;
        }

        $payload = array_merge([
            'borrow_request_id' => $borrowItemInstance->borrow_request_id,
            'walk_in_request_id' => $borrowItemInstance->walk_in_request_id,
            'borrower_name' => $this->resolveBorrowerName($borrowItemInstance),
            'item_condition' => $borrowItemInstance->return_condition ?? 'pending',
            'date_returned' => optional($borrowItemInstance->returned_at)->toDateTimeString(),
            'condition_updated_at' => optional($borrowItemInstance->condition_updated_at)->toDateTimeString(),
            'item_id' => $borrowItemInstance->item_id,
            'item_name' => $borrowItemInstance->item?->name,
            'property_number' => $itemInstance->property_number,
            'status' => $itemInstance->status,
        ], $extra);

        $this->eventLogger->log($itemInstance, $action, array_filter($payload, fn ($value) => $value !== null && $value !== ''), $actor);
    }

    private function resolveBorrowerName(BorrowItemInstance $borrowItemInstance): ?string
    {
        $borrowRequest = $borrowItemInstance->borrowRequest;
        if ($borrowRequest?->user) {
            $first = $borrowRequest->user->first_name ?? '';
            $last = $borrowRequest->user->last_name ?? '';
            $full = trim("$first $last");
            if ($full !== '') {
                return $full;
            }
            if (! empty($borrowRequest->user->name)) {
                return $borrowRequest->user->name;
            }
        }

        $walkIn = $borrowItemInstance->walkInRequest;
        if ($walkIn && ! empty($walkIn->borrower_name)) {
            return $walkIn->borrower_name;
        }

        return null;
    }
}
