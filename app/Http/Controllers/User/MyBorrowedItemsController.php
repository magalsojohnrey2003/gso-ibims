<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BorrowRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\BorrowRequestFormPdf;

class MyBorrowedItemsController extends Controller
{
    public function index()
    {
        return view('user.my-borrowed-items.index');
    }

    public function print(BorrowRequest $borrowRequest, BorrowRequestFormPdf $borrowRequestFormPdf)
    {
        if ($borrowRequest->user_id !== Auth::id()) {
            abort(403);
        }

        $result = $borrowRequestFormPdf->render($borrowRequest);

        return response($result['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $result['filename'] . '"',
            'Content-Length' => strlen($result['content']),
        ]);
    }

    public function list(Request $request)
    {
        $userId = Auth::id();

        $requests = BorrowRequest::with(['items.item'])
            ->where('user_id', $userId)
            ->latest()
            ->get()
            ->map(function ($borrowRequest) {
                $status = $borrowRequest->status === 'qr_verified' ? 'approved' : $borrowRequest->status;

                $items = $borrowRequest->items->map(function ($reqItem) {
                    return [
                        'quantity' => $reqItem->quantity,
                        'assigned_manpower' => $reqItem->assigned_manpower ?? 0,
                        'manpower_role' => $reqItem->manpower_role ?? null,
                        'manpower_notes' => $reqItem->manpower_notes ?? null,
                        'item' => [
                            'id' => $reqItem->item->id ?? null,
                            'name' => $reqItem->item->name ?? 'Unknown',
                        ],
                    ];
                })->values();

                $instances = $this->fetchBorrowedInstances($borrowRequest->id);

                return [
                    'id' => $borrowRequest->id,
                    'borrow_date' => $borrowRequest->borrow_date,
                    'return_date' => $borrowRequest->return_date,
                    'time_of_usage' => $borrowRequest->time_of_usage,
                    'status' => $status,
                    'manpower_count' => $borrowRequest->manpower_count,
                    'location' => $borrowRequest->location, 
                    'purpose_office' => $borrowRequest->purpose_office,
                    'purpose' => $borrowRequest->purpose,
                    'reject_category' => $borrowRequest->reject_category,
                    'rejection_reason' => $borrowRequest->reject_reason,
                    'items' => $items,
                    'borrowed_instances' => $instances,
                    'delivery_reason_type' => $borrowRequest->delivery_reason_type,
                    'delivery_reason_details' => $borrowRequest->delivery_reason_details,
                ];
            });

        return response()->json($requests);
    }

    public function show(BorrowRequest $borrowRequest)
    {
        if ($borrowRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $borrowRequest->load('items.item');

        $items = $borrowRequest->items->map(function ($reqItem) {
            return [
                'quantity' => $reqItem->quantity,
                'assigned_manpower' => $reqItem->assigned_manpower ?? 0,
                'manpower_role' => $reqItem->manpower_role ?? null,
                'manpower_notes' => $reqItem->manpower_notes ?? null,
                'item' => [
                    'id' => $reqItem->item->id ?? null,
                    'name' => $reqItem->item->name ?? 'Unknown',
                ],
            ];
        })->values();

        $instances = $this->fetchBorrowedInstances($borrowRequest->id);
        $status = $borrowRequest->status === 'qr_verified' ? 'approved' : $borrowRequest->status;

        return response()->json([
            'id' => $borrowRequest->id,
            'borrow_date' => $borrowRequest->borrow_date,
            'return_date' => $borrowRequest->return_date,
            'time_of_usage' => $borrowRequest->time_of_usage,
            'status' => $status,
            'manpower_count' => $borrowRequest->manpower_count,
            'location' => $borrowRequest->location,
            'purpose_office' => $borrowRequest->purpose_office,
            'purpose' => $borrowRequest->purpose,
            'reject_category' => $borrowRequest->reject_category,
            'rejection_reason' => $borrowRequest->reject_reason,
            'items' => $items,
            'borrowed_instances' => $instances,
            'delivery_reason_type' => $borrowRequest->delivery_reason_type,
            'delivery_reason_details' => $borrowRequest->delivery_reason_details,
        ]);
    }

    protected function fetchBorrowedInstances(int $borrowRequestId)
    {
        if (class_exists(\App\Models\BorrowItemInstance::class)) {
            $rows = \App\Models\BorrowItemInstance::where('borrow_request_id', $borrowRequestId)
                ->with(['item', 'instance'])
                ->get()
                ->map(function ($inst) {
                    return [
                        'id' => $inst->id,
                        'item_id' => $inst->item_id ?? ($inst->item->id ?? null),
                        'item_instance_id' => $inst->item_instance_id ?? ($inst->instance->id ?? null),
                        'serial' => $inst->instance->serial ?? null,
                        'property_number' => $inst->instance->property_number ?? null,
                        'item' => [
                            'id' => $inst->item->id ?? null,
                            'name' => $inst->item->name ?? null,
                        ],
                    ];
                });
            return $rows;
        }

        if (SchemaHasTable('borrow_item_instances')) {
            $rows = DB::table('borrow_item_instances')
                ->where('borrow_item_instances.borrow_request_id', $borrowRequestId)
                ->leftJoin('items', 'borrow_item_instances.item_id', '=', 'items.id')
                ->leftJoin('item_instances', 'borrow_item_instances.item_instance_id', '=', 'item_instances.id')
                ->select(
                    'borrow_item_instances.id',
                    'borrow_item_instances.item_id',
                    'borrow_item_instances.item_instance_id',
                    'item_instances.serial',
                    'item_instances.property_number',
                    'items.id as item_id_from_items',
                    'items.name as item_name'
                )
                ->get()
                ->map(function ($r) {
                    $itemId = $r->item_id ?? $r->item_id_from_items ?? null;
                    return [
                        'id' => $r->id,
                        'item_id' => $itemId,
                        'item_instance_id' => $r->item_instance_id ?? null,
                        'serial' => $r->serial ?? null,
                        'property_number' => $r->property_number ?? null,
                        'item' => [
                            'id' => $itemId,
                            'name' => $r->item_name ?? null,
                        ],
                    ];
                });
            return $rows;
        }
        return collect([]);
    }
}

/**
 * Helper function used above to avoid fatal errors if the table doesn't exist in some environments.
 * You can remove this helper and use Schema::hasTable(...) directly if you prefer importing Schema.
 */
if (! function_exists('SchemaHasTable')) {
    function SchemaHasTable(string $table): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
}
