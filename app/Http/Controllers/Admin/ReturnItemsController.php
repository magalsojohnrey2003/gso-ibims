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
        $requests = BorrowRequest::with(['user', 'borrowedInstances'])
            ->whereIn('delivery_status', ['dispatched', 'returned'])
            ->orderByDesc('dispatched_at')
            ->get()
            ->map(function (BorrowRequest $request) {
                $conditionKey = $this->summarizeCondition($request);

                return [
                    'id' => $request->id,
                    'borrow_request_id' => $request->id,
                    'borrower_name' => $this->formatBorrowerName($request),
                    'status' => $request->status ?? 'pending',
                    'delivery_status' => $request->delivery_status ?? 'pending',
                    'condition' => $conditionKey,
                    'condition_label' => $this->formatConditionLabel($conditionKey),
                    'borrow_date' => $request->borrow_date?->toDateString(),
                    'return_date' => $request->return_date?->toDateString(),
                ];
            });

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

            return [
                'id' => $instance->id,
                'property_label' => $label,
                'item_name' => $instance->item?->name ?? 'Unknown',
                'condition' => $instance->return_condition ?? 'pending',
                'returned_at' => $instance->returned_at?->toDateTimeString(),
            ];
        });

        return response()->json([
            'id' => $borrowRequest->id,
            'borrow_request_id' => $borrowRequest->id,
            'borrower' => $this->formatBorrowerName($borrowRequest),
            'address' => $borrowRequest->location ?? '',
            'status' => $borrowRequest->status ?? 'pending',
            'delivery_status' => $borrowRequest->delivery_status ?? 'pending',
            'borrow_date' => $borrowRequest->borrow_date?->toDateString(),
            'return_date' => $borrowRequest->return_date?->toDateString(),
            'items' => $items,
            'condition_summary' => $this->formatConditionLabel($this->summarizeCondition($borrowRequest)),
        ]);
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
                'borrow_summary' => $borrowRequest
                    ? $this->formatConditionLabel($this->summarizeCondition($borrowRequest))
                    : null,
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

        $conditions = $instances->pluck('return_condition')->map(function ($value) {
            return $value ?: 'pending';
        });

        if ($conditions->contains('missing')) {
            return 'missing';
        }

        if ($conditions->contains('damage')) {
            return 'damage';
        }

        if ($conditions->contains('minor_damage')) {
            return 'minor_damage';
        }

        if ($conditions->every(fn ($c) => $c === 'good')) {
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
