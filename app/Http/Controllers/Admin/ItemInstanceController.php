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
            'ppe' => 'sometimes|string',
            'serial' => 'sometimes|string',
            'office' => 'sometimes|string|max:4',
        ]);

        // Accept a full property_number, or components
        try {
            if (!empty($data['property_number'])) {
                $parsed = $numbers->parse($data['property_number']);
            } else {
                // build components - require year/ppe/serial/office
                $components = [
                    'year' => $data['year'] ?? $instance->year_procured,
                    'ppe' => $data['ppe'] ?? $instance->ppe_code,
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
            $instance->ppe_code = $parsed['ppe'];
            $instance->serial = $parsed['serial'];
            $instance->serial_int = $parsed['serial_int'] ?? null;
            $instance->office_code = $parsed['office'];
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
}
