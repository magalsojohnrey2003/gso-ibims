<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BorrowItemInstance;
use App\Models\BorrowRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnItemsController extends Controller
{
    public function index()
    {
        return view('admin.return-items.index');
    }

    public function list()
    {
        // Get regular borrow requests
        $borrowRequests = BorrowRequest::with(['user', 'borrowedInstances'])
            // Include delivered so requests remain visible for return processing after delivery
            ->whereIn('delivery_status', ['dispatched', 'delivered', 'returned'])
            ->orderByDesc('dispatched_at')
            ->get()
            ->map(function (BorrowRequest $request) {
                $conditionKey = $this->summarizeCondition($request);

                return [
                    'id' => $request->id,
                    'borrow_request_id' => $request->id,
                    'walk_in_request_id' => null,
                    'request_type' => 'regular',
                    'borrower_name' => $this->formatBorrowerName($request),
                    'status' => $request->status ?? 'pending',
                    'delivery_status' => $request->delivery_status ?? 'pending',
                    'condition' => $conditionKey,
                    'condition_label' => $this->formatConditionLabel($conditionKey),
                    'borrow_date' => $request->borrow_date?->toDateString(),
                    'return_date' => $request->return_date?->toDateString(),
                    'return_timestamp' => $request->delivered_at?->toDateTimeString(),
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
                    'borrower_name' => $walkIn->borrower_name ?? 'Walk-in Borrower',
                    'status' => $walkIn->status,
                    'delivery_status' => $deliveryStatus,
                    'condition' => $conditionKey,
                    'condition_label' => $this->formatConditionLabel($conditionKey),
                    'borrow_date' => $walkIn->borrowed_at?->toDateString(),
                    'return_date' => $walkIn->returned_at?->toDateString(),
                    'return_timestamp' => $walkIn->updated_at?->toDateTimeString(),
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
        ]);

        $items = $borrowRequest->borrowedInstances->map(function (BorrowItemInstance $instance) {
            $label = $instance->instance?->property_number
                ?? $instance->instance?->serial
                ?? ($instance->item?->name ?? 'Untracked Item');

            $condition = $instance->return_condition ?? 'pending';

            return [
                'id' => $instance->id,
                'property_label' => $label,
                'item_name' => $instance->item?->name ?? 'Unknown Item',
                'condition' => $condition,
                'condition_label' => $this->formatConditionLabel($condition),
                'returned_at' => $instance->returned_at?->toDateTimeString(),
                'inventory_status' => $instance->instance?->status ?? 'unknown',
                'inventory_status_label' => $this->formatInventoryStatus($instance->instance?->status),
            ];
        })->values();

        $itemOptions = $items
            ->groupBy('item_name')
            ->map(fn ($group, $name) => ['name' => $name, 'count' => $group->count()])
            ->values()
            ->all();

        return response()->json([
            'id' => $borrowRequest->id,
            'borrow_request_id' => $borrowRequest->id,
            'borrower' => $this->formatBorrowerName($borrowRequest),
            'address' => $borrowRequest->location ?? '',
            'status' => $borrowRequest->status ?? 'pending',
            'delivery_status' => $borrowRequest->delivery_status ?? 'pending',
            'borrow_date' => $borrowRequest->borrow_date?->toDateString(),
            'return_date' => $borrowRequest->return_date?->toDateString(),
            'return_timestamp' => $borrowRequest->delivered_at?->toDateTimeString(),
            'items' => $items,
            'item_options' => $itemOptions,
            'default_item' => $itemOptions[0]['name'] ?? null,
            'condition_summary' => $this->formatConditionLabel($this->summarizeCondition($borrowRequest)),
        ]);
    }

    public function collect(BorrowRequest $borrowRequest)
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
            ]);

            foreach ($borrowRequest->borrowedInstances as $instance) {
                $previousCondition = $instance->return_condition ?? 'pending';

                if (! $instance->returned_at) {
                    $instance->returned_at = now();
                }

                $instance->condition_updated_at = now();
                $instance->return_condition = 'good';
                $instance->save();

                $instance->loadMissing(['item', 'instance']);
                $this->syncInventoryForConditionChange($instance, $previousCondition, 'good');
            }

            $borrowRequest->status = 'returned';
            $borrowRequest->delivery_status = 'returned';
            if (! $borrowRequest->delivered_at) {
                $borrowRequest->delivered_at = now();
            }
            $borrowRequest->save();

            $borrowRequest->load('borrowedInstances.instance', 'borrowedInstances.item');

            DB::commit();

        $instancesPayload = $borrowRequest->borrowedInstances->map(function (BorrowItemInstance $instance) {
            return [
                'borrow_item_instance_id' => $instance->id,
                'item_instance_id' => $instance->item_instance_id,
                'item_id' => $instance->item_id,
                'status' => $instance->instance?->status,
                'inventory_status_label' => $this->formatInventoryStatus($instance->instance?->status),
                'condition' => $instance->return_condition ?? 'pending',
                'condition_label' => $this->formatConditionLabel($instance->return_condition ?? 'pending'),
            ];
        })->values();

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

        $instances = BorrowItemInstance::with(['instance', 'item'])
            ->where('walk_in_request_id', $id)
            ->get();

        $items = $instances->map(function (BorrowItemInstance $instance) {
            $label = $instance->instance?->property_number
                ?? $instance->instance?->serial
                ?? ($instance->item?->name ?? 'Untracked Item');

            $condition = $instance->return_condition ?? 'pending';

            return [
                'id' => $instance->id,
                'property_label' => $label,
                'item_name' => $instance->item?->name ?? 'Unknown Item',
                'condition' => $condition,
                'condition_label' => $this->formatConditionLabel($condition),
                'returned_at' => $instance->returned_at?->toDateTimeString(),
                'inventory_status' => $instance->instance?->status ?? 'unknown',
                'inventory_status_label' => $this->formatInventoryStatus($instance->instance?->status),
            ];
        })->values();

        $itemOptions = $items
            ->groupBy('item_name')
            ->map(fn ($group, $name) => ['name' => $name, 'count' => $group->count()])
            ->values()
            ->all();

        return response()->json([
            'id' => 'W' . $id,
            'walk_in_request_id' => $id,
            'request_type' => 'walk-in',
            'borrower' => $walkInRequest->borrower_name ?? 'Walk-in Borrower',
            'address' => $walkInRequest->office_agency ?? '',
            'status' => 'delivered',
            'delivery_status' => 'delivered',
            'borrow_date' => $walkInRequest->borrowed_at?->toDateString(),
            'return_date' => $walkInRequest->returned_at?->toDateString(),
            'return_timestamp' => $walkInRequest->updated_at?->toDateTimeString(),
            'items' => $items,
            'item_options' => $itemOptions,
            'default_item' => $itemOptions[0]['name'] ?? null,
            'condition_summary' => $this->formatConditionLabel($this->summarizeConditionForInstances($instances)),
        ]);
    }

    public function collectWalkIn($id)
    {
        $walkInRequest = \App\Models\WalkInRequest::findOrFail($id);

        DB::beginTransaction();
        try {
            $instances = BorrowItemInstance::with(['instance', 'item'])
                ->where('walk_in_request_id', $id)
                ->get();

            foreach ($instances as $instance) {
                $previousCondition = $instance->return_condition ?? 'pending';

                if (! $instance->returned_at) {
                    $instance->returned_at = now();
                }

                $instance->condition_updated_at = now();
                $instance->return_condition = 'good';
                $instance->save();

                // Sync inventory based on condition change
                $this->syncInventoryForConditionChange($instance, $previousCondition, 'good');
            }

            // Update walk-in request status to 'returned'
            $walkInRequest->status = 'returned';
            $walkInRequest->returned_at = now();
            $walkInRequest->save();

            DB::commit();

            $instancesPayload = $instances->map(function (BorrowItemInstance $instance) {
                return [
                    'borrow_item_instance_id' => $instance->id,
                    'item_instance_id' => $instance->item_instance_id,
                    'item_id' => $instance->item_id,
                    'status' => $instance->instance?->status,
                    'inventory_status_label' => $this->formatInventoryStatus($instance->instance?->status),
                    'condition' => $instance->return_condition ?? 'pending',
                    'condition_label' => $this->formatConditionLabel($instance->return_condition ?? 'pending'),
                ];
            })->values();

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
            'condition' => 'required|in:good,missing,damage,minor_damage',
        ]);

        $newCondition = $data['condition'];
        $previousCondition = $borrowItemInstance->return_condition ?? 'pending';

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
                $this->maybeMarkReturned($borrowRequest);
            }

            DB::commit();

            return response()->json([
                'message' => 'Condition updated successfully.',
                'condition' => $borrowItemInstance->return_condition,
                'condition_label' => $this->formatConditionLabel($borrowItemInstance->return_condition),
                'inventory_status' => $borrowItemInstance->instance?->status,
                'inventory_status_label' => $this->formatInventoryStatus($borrowItemInstance->instance?->status),
                'item_instance_id' => $borrowItemInstance->item_instance_id,
                'item_id' => $borrowItemInstance->item_id,
                'available_qty' => optional($borrowItemInstance->item)->available_qty,
                'borrow_summary' => $borrowRequest
                    ? $this->formatConditionLabel($this->summarizeCondition($borrowRequest))
                    : null,
                'latest_status' => $borrowRequest?->status,
                'latest_delivery_status' => $borrowRequest?->delivery_status,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update condition.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
}
