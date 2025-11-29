<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BorrowItemInstance;
use App\Models\BorrowRequest;
use App\Models\ItemInstance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Services\BorrowRequestFormPdf;
use App\Services\PhilSmsService;
use App\Notifications\RequestNotification;

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
                    'formatted_request_id' => $borrowRequest->formatted_request_id,
                    'borrow_date' => $borrowRequest->borrow_date,
                    'return_date' => $borrowRequest->return_date,
                    'time_of_usage' => $borrowRequest->time_of_usage,
                    'status' => $status,
                    'delivery_status' => $borrowRequest->delivery_status,
                    'manpower_count' => $borrowRequest->manpower_count,
                    'location' => $borrowRequest->location, 
                    'purpose_office' => $borrowRequest->purpose_office,
                    'purpose' => $borrowRequest->purpose,
                    'reject_category' => $borrowRequest->reject_category,
                    'reject_reason' => $borrowRequest->reject_reason,
                    'items' => $items,
                    'borrowed_instances' => $instances,
                    'delivery_reason_type' => $borrowRequest->delivery_reason_type,
                    'delivery_reason_details' => $borrowRequest->delivery_reason_details,
                    'delivered_at' => optional($borrowRequest->delivered_at)->toIso8601String(),
                    'delivery_reported_at' => optional($borrowRequest->delivery_reported_at)->toIso8601String(),
                    'delivery_report_reason' => $borrowRequest->delivery_report_reason,
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
            'formatted_request_id' => $borrowRequest->formatted_request_id,
            'borrow_date' => $borrowRequest->borrow_date,
            'return_date' => $borrowRequest->return_date,
            'time_of_usage' => $borrowRequest->time_of_usage,
            'status' => $status,
            'delivery_status' => $borrowRequest->delivery_status,
            'manpower_count' => $borrowRequest->manpower_count,
            'location' => $borrowRequest->location,
            'purpose_office' => $borrowRequest->purpose_office,
            'purpose' => $borrowRequest->purpose,
            'reject_category' => $borrowRequest->reject_category,
            'reject_reason' => $borrowRequest->reject_reason,
            'items' => $items,
            'borrowed_instances' => $instances,
            'delivery_reason_type' => $borrowRequest->delivery_reason_type,
            'delivery_reason_details' => $borrowRequest->delivery_reason_details,
            'delivered_at' => optional($borrowRequest->delivered_at)->toIso8601String(),
            'delivery_reported_at' => optional($borrowRequest->delivery_reported_at)->toIso8601String(),
            'delivery_report_reason' => $borrowRequest->delivery_report_reason,
        ]);
    }

    public function confirmDelivery(Request $request, BorrowRequest $borrowRequest, PhilSmsService $philSms)
    {
        if ($borrowRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($borrowRequest->delivery_status === 'delivered') {
            return response()->json(['message' => 'Already confirmed delivered.'], 200);
        }

        if ($borrowRequest->delivery_status !== 'dispatched') {
            return response()->json(['message' => 'Only dispatched requests can be confirmed.'], 422);
        }

        DB::beginTransaction();
        try {
            $borrowRequest->load('items.item', 'borrowedInstances.instance');

            if ($borrowRequest->borrowedInstances->isEmpty()) {
                foreach ($borrowRequest->items as $requestItem) {
                    $item = $requestItem->item;
                    if (! $item) {
                        throw new \RuntimeException('One of the requested items could not be found.');
                    }

                    $needed = (int) $requestItem->quantity;
                    if ($needed <= 0) {
                        continue;
                    }

                    $availableInstances = ItemInstance::where('item_id', $item->id)
                        ->where('status', 'available')
                        ->lockForUpdate()
                        ->limit($needed)
                        ->get();

                    if ($availableInstances->count() < $needed) {
                        throw new \RuntimeException("Not enough available instances for {$item->name}.");
                    }

                    foreach ($availableInstances as $instance) {
                        $instance->status = 'allocated';
                        $instance->save();

                        BorrowItemInstance::create([
                            'borrow_request_id' => $borrowRequest->id,
                            'item_id' => $item->id,
                            'item_instance_id' => $instance->id,
                            'borrowed_qty' => 1,
                            'checked_out_at' => now(),
                            'expected_return_at' => $borrowRequest->return_date,
                            'return_condition' => 'pending',
                        ]);
                    }
                }

                $borrowRequest->load('items.item', 'borrowedInstances.instance');
            }

            foreach ($borrowRequest->items as $requestItem) {
                $item = $requestItem->item;
                if (! $item) {
                    continue;
                }

                $borrowRequest->borrowedInstances()
                    ->where('item_id', $item->id)
                    ->whereHas('instance', function ($query) {
                        $query->where('status', 'allocated');
                    })
                    ->get()
                    ->each(function (BorrowItemInstance $borrowInstance) {
                        if ($borrowInstance->instance) {
                            $borrowInstance->instance->status = 'borrowed';
                            $borrowInstance->instance->save();
                        }
                    });

                $item->available_qty = max(0, (int) $item->available_qty - (int) $requestItem->quantity);
                $item->save();
            }

            $borrowRequest->delivery_status = 'delivered';
            $borrowRequest->delivered_at = now();
            $borrowRequest->delivery_reported_at = null;
            $borrowRequest->delivery_report_reason = null;
            $borrowRequest->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('user.confirm_delivery_failed', [
                'borrow_request_id' => $borrowRequest->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to confirm delivery. Please try again.',
            ], 500);
        }

        $borrowRequest->refresh();
        $borrower = $borrowRequest->user;
        $borrowerName = $borrower
            ? trim(($borrower->first_name ?? '') . ' ' . ($borrower->last_name ?? ''))
            : 'Borrower';

        $payload = [
            'type' => 'delivery_confirmed',
            'message' => sprintf('%s confirmed receipt for %s.', $borrowerName ?: 'Borrower', $borrowRequest->formatted_request_id ?? ('Request #' . $borrowRequest->id)),
            'borrow_request_id' => $borrowRequest->id,
            'formatted_request_id' => $borrowRequest->formatted_request_id,
            'user_id' => $borrowRequest->user_id,
            'user_name' => $borrowerName,
            'actor_id' => $borrower?->id,
            'actor_name' => $borrowerName,
            'delivered_at' => $borrowRequest->delivered_at?->toDateTimeString(),
        ];

        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new RequestNotification($payload));

        try {
            $fresh = $borrowRequest->fresh(['user']);
            if ($fresh) {
                $philSms->notifyBorrowerDelivery($fresh);
            }
        } catch (\Throwable $e) {
            Log::warning('user.confirm_delivery_sms_failed', [
                'borrow_request_id' => $borrowRequest->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['message' => 'Confirmed receipt.']);
    }

    public function reportNotReceived(Request $request, BorrowRequest $borrowRequest)
    {
        if ($borrowRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:2000',
        ]);

        try {
            $borrowRequest->markNotReceived($validated['reason'] ?? null);
        } catch (\Throwable $e) {
            Log::error('user.report_not_received_failed', [
                'borrow_request_id' => $borrowRequest->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to submit report. Please try again.',
            ], 500);
        }

        $borrowRequest->refresh();
        $borrower = $borrowRequest->user;
        $borrowerName = $borrower
            ? trim(($borrower->first_name ?? '') . ' ' . ($borrower->last_name ?? ''))
            : 'Borrower';

        $payload = [
            'type' => 'delivery_reported',
            'message' => sprintf('%s reported a delivery issue for %s.', $borrowerName ?: 'Borrower', $borrowRequest->formatted_request_id ?? ('Request #' . $borrowRequest->id)),
            'borrow_request_id' => $borrowRequest->id,
            'formatted_request_id' => $borrowRequest->formatted_request_id,
            'user_id' => $borrowRequest->user_id,
            'user_name' => $borrowerName,
            'actor_id' => $borrower?->id,
            'actor_name' => $borrowerName,
            'reason' => $borrowRequest->delivery_report_reason,
            'reported_at' => $borrowRequest->delivery_reported_at?->toDateTimeString(),
        ];

        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new RequestNotification($payload));

        return response()->json(['message' => 'Reported not received.']);
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
