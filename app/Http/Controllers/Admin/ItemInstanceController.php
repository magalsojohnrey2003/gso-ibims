<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemInstance;
use App\Services\PropertyNumberService;
use App\Services\ItemInstanceEventLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ItemInstanceController extends Controller
{
    public function __construct(private ItemInstanceEventLogger $logger) {}

    // PATCH admin/item-instances/{instance}
    public function update(Request $request, ItemInstance $instance, PropertyNumberService $numbers)
    {
        // Only admin middleware applied in routes group
        $data = $request->validate([
            'property_number' => 'sometimes|string',
            'year' => 'sometimes|digits:4',
            'category' => 'sometimes|digits:4',
            'gla' => 'sometimes|digits_between:1,4',
            'serial' => ['sometimes', 'regex:/^\d{4}[A-Za-z]?$/'],
            'office' => 'sometimes|digits:4',
            'serial_no' => ['sometimes', 'nullable', 'string', 'max:4', 'regex:/^[A-Za-z0-9]*$/'],
            'model_no' => ['sometimes', 'nullable', 'string', 'max:15', 'regex:/^[A-Za-z0-9]*$/'],
        ]);

        // Accept a full property_number, or components
        try {
            if (!empty($data['property_number'])) {
                $parsed = $numbers->parse($data['property_number']);
            } else {
                // build components - require year/category/gla/serial/office (fall back to existing instance)
                $components = [
                    'year' => $data['year'] ?? $instance->year_procured,
                    'category' => $data['category'] ?? $instance->category_code ?? '',
                    'gla' => isset($data['gla']) ? (string) $data['gla'] : ($instance->gla ?? null),
                    'serial' => $data['serial'] ?? $instance->serial,
                    'office' => $data['office'] ?? $instance->office_code,
                ];
                $parsed = $numbers->parse($numbers->assemble($components)); // ensure canonicalization
            }
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => 'Invalid property number: ' . $e->getMessage()], 422);
        }

        // uniqueness check (exclude current instance)
        $exists = ItemInstance::where('property_number', $parsed['property_number'])
            ->where('id', '!=', $instance->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Property number already in use.'], 409);
        }

        DB::beginTransaction();
        try {
            $instance->property_number = $parsed['property_number'];
            $instance->year_procured = (int) $parsed['year'];
            $instance->category_code = isset($parsed['category']) ? $parsed['category'] : null;
            $instance->gla = isset($parsed['gla']) ? $parsed['gla'] : null;
            $instance->serial = $parsed['serial'];
            $instance->serial_int = $parsed['serial_int'] ?? null;
            $instance->office_code = $parsed['office'];
            if (array_key_exists('serial_no', $data)) {
                $value = $data['serial_no'];
                $instance->serial_no = $value !== null ? substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value)), 0, 4) : null;
            }
            if (array_key_exists('model_no', $data)) {
                $value = $data['model_no'];
                $instance->model_no = $value !== null ? substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value)), 0, 15) : null;
            }
            $instance->save();

            $this->logger->log($instance, 'updated', ['property_number' => $instance->property_number], $request->user());

            // refresh item counts
            $item = $instance->item;
            if ($item) {
                $item->total_qty = $item->instances()->count();
                $item->available_qty = $item->instances()->where('status', 'available')->count();
                $item->save();
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'instance' => $instance->fresh(),
                'item' => [
                    'id' => $item->id ?? null,
                    'total_qty' => $item->total_qty ?? 0,
                    'available_qty' => $item->available_qty ?? 0,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update instance: ' . $e->getMessage()], 500);
        }
    }

    public function updateCondition(Request $request, ItemInstance $instance)
    {
        $data = $request->validate([
            'condition' => 'required|in:good,minor_damage,damage,missing',
        ]);

        $condition = $data['condition'];
        $nextStatus = $this->mapConditionToInstanceStatus($condition);

        if (strtolower((string) $instance->status) === 'borrowed') {
            return response()->json([
                'message' => 'Cannot update condition while the item is currently borrowed.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $instance->status = $nextStatus;
            $instance->save();

            $item = $instance->item;
            if ($item) {
                $item->available_qty = $item->instances()->where('status', 'available')->count();
                $item->save();
            }

            $this->logger->log($instance, 'condition_updated', [
                'condition' => $condition,
                'status' => $nextStatus,
            ], $request->user());

            DB::commit();

            return response()->json([
                'message' => 'Condition updated successfully.',
                'condition' => $condition,
                'condition_label' => $this->formatConditionLabel($condition),
                'inventory_status' => $instance->status,
                'inventory_status_label' => $this->formatStatusLabel($instance->status),
                'item_instance_id' => $instance->id,
                'item_id' => $item->id ?? null,
                'available_qty' => $item->available_qty ?? null,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update condition.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // DELETE admin/item-instances/{instance}
    public function destroy(Request $request, ItemInstance $instance)
    {
        // Check borrow constraints: cannot delete if any active borrow exists
        $activeStatuses = ['approved', 'borrowed', 'issued', 'checked_out'];

        $borrowed = $instance->borrowRecords()->whereHas('borrowRequest', function ($q) use ($activeStatuses) {
            $q->whereIn('status', $activeStatuses);
        })->exists();

        if ($borrowed || ($instance->status && in_array(strtolower($instance->status), $activeStatuses, true))) {
            return response()->json(['message' => 'Instance cannot be deleted because it is currently borrowed/active.'], 409);
        }

        DB::beginTransaction();
        try {
            $item = $instance->item;

            $this->logger->log($instance, 'deleted', ['property_number' => $instance->property_number], $request->user());
            $instance->delete();

            if ($item) {
                $item->total_qty = $item->instances()->count();
                $item->available_qty = $item->instances()->where('status', 'available')->count();
                $item->save();
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'item' => [
                    'id' => $item->id ?? null,
                    'total_qty' => $item->total_qty ?? 0,
                    'available_qty' => $item->available_qty ?? 0,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete instance: ' . $e->getMessage()], 500);
        }
    }

    private function formatConditionLabel(string $condition): string
    {
        return match ($condition) {
            'good' => 'Good',
            'missing' => 'Missing',
            'damage' => 'Damage',
            'minor_damage' => 'Minor Damage',
            default => ucwords(str_replace('_', ' ', $condition)),
        };
    }

    private function formatStatusLabel(?string $status): string
    {
        if (! $status) {
            return 'Unknown';
        }

        return ucwords(str_replace('_', ' ', strtolower($status)));
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
}
