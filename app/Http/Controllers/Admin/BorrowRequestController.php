<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BorrowRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\BorrowRequestStatusUpdated;
use App\Notifications\RequestNotification;
use App\Models\BorrowItemInstance;
use App\Models\BorrowRequestItem;

class BorrowRequestController extends Controller
{
    public function index()
    {
        return view('admin.borrow-requests.index');
    }
    
    public function list()
    {
        $requests = BorrowRequest::with(['user', 'items.item'])
            ->latest()
            ->get();

        return response()->json($requests);
    }

    public function updateStatus(Request $request, BorrowRequest $borrowRequest)
    {
        $request->validate([
            'status' => 'required|in:pending,validated,approved,rejected,returned,return_pending'
        ]);

        $old = $borrowRequest->status;
        $new = $request->status;

        if ($old === $new) {
            return response()->json([
                'message' => 'No change',
                'status'  => $borrowRequest->status
            ]);
        }

        DB::beginTransaction();
        try {
            $borrowRequest->load('items.item');

            $assignmentWarning = null;
            // When admin saves assignments we now mark the request as "validated" (intermediate step).
            // Save manpower assignments when new status is 'validated'.
            if ($new === 'validated') {
                $assignments = $request->input('manpower_assignments', []);
                $totalAssigned = 0;

                if ($assignments && is_array($assignments)) {
                    foreach ($assignments as $a) {
                        $briId = isset($a['borrow_request_item_id']) ? (int) $a['borrow_request_item_id'] : null;
                        $assignedMana = isset($a['assigned_manpower']) ? (int) $a['assigned_manpower'] : 0;
                        $role = isset($a['manpower_role']) ? substr($a['manpower_role'], 0, 100) : null;
                        $notes = isset($a['manpower_notes']) ? substr($a['manpower_notes'], 0, 2000) : null;

                        $bri = BorrowRequestItem::where('id', $briId)
                            ->where('borrow_request_id', $borrowRequest->id)
                            ->first();

                        if (! $bri) {
                            DB::rollBack();
                            return response()->json(['message' => 'Invalid manpower assignment row.'], 422);
                        }

                        $bri->assigned_manpower = $assignedMana;
                        $bri->manpower_role = $role;
                        $bri->manpower_notes = $notes;
                        $bri->assigned_by = \Illuminate\Support\Facades\Auth::id();
                        $bri->assigned_at = now();
                        $bri->save();

                        $totalAssigned += $assignedMana;
                    }
                }

                $requested = (int) $borrowRequest->manpower_count;
                if ($totalAssigned > $requested) {
                    $assignmentWarning = "Total assigned manpower ({$totalAssigned}) exceeds requested ({$requested}).";
                }
            }

            if ($old !== 'approved' && $new === 'approved') {
                foreach ($borrowRequest->items as $reqItem) {
                    $item = $reqItem->item;
                    if (! $item) {
                        DB::rollBack();
                        return response()->json(['message' => 'Item not found for a request row.'], 422);
                    }

                    $needed = (int) $reqItem->quantity;

                    $availableInstances = \App\Models\ItemInstance::where('item_id', $item->id)
                        ->where('status', 'available')
                        ->lockForUpdate()
                        ->limit($needed)
                        ->get();

                    $availableCount = $availableInstances->count();

                    if ($availableCount < $needed) {
                        DB::rollBack();
                        $shortfall = max(0, $needed - $availableCount);
                        $message = $availableCount > 0
                            ? "Only {$availableCount} of {$item->name} available right now (needed {$needed})."
                            : "No available instances for {$item->name}.";

                        return response()->json([
                            'message' => $message,
                            'available_instances' => $availableCount,
                            'requested_quantity' => $needed,
                            'shortfall' => $shortfall,
                        ], 422);
                    }

                    foreach ($availableInstances as $inst) {
                        $inst->status = 'borrowed';
                        $inst->save();

                        BorrowItemInstance::create([
                            'borrow_request_id' => $borrowRequest->id,
                            'item_id'           => $item->id,
                            'item_instance_id'  => $inst->id,
                            'checked_out_at'    => now(),
                            'expected_return_at'=> $borrowRequest->return_date,
                        ]);
                    }

                    $item->available_qty = max(0, (int) $item->available_qty - $needed);
                    $item->save();
                }
            }

            if ($old === 'approved' && $new !== 'approved') {
                $allocRows = \App\Models\BorrowItemInstance::where('borrow_request_id', $borrowRequest->id)
                    ->whereNull('returned_at')
                    ->get();

                foreach ($allocRows as $row) {
                    $inst = $row->instance;
                    if ($inst) {
                        $inst->status = 'available';
                        $inst->save();
                    }
                    $row->delete();
                }

                foreach ($borrowRequest->items as $reqItem) {
                    $reqItem->assigned_manpower = 0;
                    $reqItem->manpower_role = null;
                    $reqItem->manpower_notes = null;
                    $reqItem->assigned_by = null;
                    $reqItem->assigned_at = null;
                    $reqItem->save();
                }

                foreach ($borrowRequest->items as $reqItem) {
                    $item = $reqItem->item;
                    if (! $item) continue;

                    $newAvailable = (int) $item->available_qty + (int) $reqItem->quantity;
                    if (isset($item->total_qty)) {
                        $item->available_qty = min((int) $item->total_qty, $newAvailable);
                    } else {
                        $item->available_qty = $newAvailable;
                    }
                    $item->save();
                }
            }

            $borrowRequest->status = $new;
            $borrowRequest->save();

            event(new BorrowRequestStatusUpdated($borrowRequest, $old, $new));

            DB::commit();

            try {
                $user = $borrowRequest->user;
                if ($user) {
                    $items = $borrowRequest->items->map(function($it) {
                        return [
                            'id' => $it->item->id ?? null,
                            'name' => $it->item->name ?? '',
                            'quantity' => $it->quantity,
                            'assigned_manpower' => $it->assigned_manpower ?? 0,
                            'manpower_role' => $it->manpower_role ?? null,
                            'manpower_notes' => $it->manpower_notes ?? null,
                        ];
                    })->toArray();

                    $payload = [
                        'type' => 'borrow_status_changed',
                        'message' => "Your borrow request #{$borrowRequest->id} was changed to {$new}.",
                        'borrow_request_id' => $borrowRequest->id,
                        'old_status' => $old,
                        'new_status' => $new,
                        'items' => $items,
                        'borrow_date' => $borrowRequest->borrow_date,
                        'return_date' => $borrowRequest->return_date,
                        'reason' => $request->input('rejection_reason') ?? null,
                        'assignment_warning' => $assignmentWarning ?? null,
                    ];

                    $user->notify(new RequestNotification($payload));
                }
            } catch (\Throwable $e) {
            }

            $response = [
                'message' => 'Status updated successfully',
                'status'  => $borrowRequest->status
            ];
            if ($assignmentWarning) $response['assignment_warning'] = $assignmentWarning;

            return response()->json($response);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update status',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    protected function allocateInstancesForBorrowRequest(BorrowRequest $borrowRequest)
    {
        // Assumes $borrowRequest->load('items.item') has been called by caller if needed.
        foreach ($borrowRequest->items as $reqItem) {
            $item = $reqItem->item;
            if (! $item) {
                throw new \RuntimeException("Item not found for request row (id: {$reqItem->id}).");
            }

            $needed = (int) $reqItem->quantity;

            $availableInstances = \App\Models\ItemInstance::where('item_id', $item->id)
                ->where('status', 'available')
                ->lockForUpdate()
                ->limit($needed)
                ->get();

            $availableCount = $availableInstances->count();

            if ($availableCount < $needed) {
                $shortfall = max(0, $needed - $availableCount);
                $message = $availableCount > 0
                    ? "Only {$availableCount} of {$item->name} available right now (needed {$needed})."
                    : "No available instances for {$item->name}.";
                throw new \RuntimeException($message);
            }

            foreach ($availableInstances as $inst) {
                $inst->status = 'borrowed';
                $inst->save();

                BorrowItemInstance::create([
                    'borrow_request_id' => $borrowRequest->id,
                    'item_id'           => $item->id,
                    'item_instance_id'  => $inst->id,
                    'checked_out_at'    => now(),
                    'expected_return_at'=> $borrowRequest->return_date,
                ]);
            }

            $item->available_qty = max(0, (int) $item->available_qty - $needed);
            $item->save();
        }
    }

    public function dispatch(Request $request, BorrowRequest $borrowRequest)
    {
        if ($borrowRequest->status !== 'validated' && $borrowRequest->status !== 'approved') {
            return response()->json(['message' => 'Only validated or approved requests can be dispatched.'], 422);
        }

        if ($borrowRequest->delivery_status === 'dispatched') {
            return response()->json(['message' => 'Already dispatched.'], 200);
        }

        DB::beginTransaction();
        try {
            // ensure we have items loaded
            $borrowRequest->load('items.item');

            // allocate item instances if status is not already approved (i.e. not allocated)
            if ($borrowRequest->status !== 'approved') {
                $this->allocateInstancesForBorrowRequest($borrowRequest);
            }

            // set approved status and delivery meta
            $borrowRequest->status = 'approved';
            $borrowRequest->delivery_status = 'dispatched';
            $borrowRequest->dispatched_at = now();
            $borrowRequest->save();

            // notify user
            $user = $borrowRequest->user;
            if ($user) {
                $payload = [
                    'type' => 'borrow_dispatched',
                    'message' => "Your borrow request #{$borrowRequest->id} is on its way.",
                    'borrow_request_id' => $borrowRequest->id,
                    'user_id' => $user->id,
                    'user_name' => trim($user->first_name . ' ' . ($user->last_name ?? '')),
                    'borrow_date' => (string) $borrowRequest->borrow_date,
                    'return_date' => (string) $borrowRequest->return_date,
                    'dispatched_at' => $borrowRequest->dispatched_at ? $borrowRequest->dispatched_at->toDateTimeString() : null,
                ];
                $user->notify(new RequestNotification($payload));
            }

            DB::commit();
            return response()->json(['message' => 'Dispatched successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to dispatch.', 'error' => $e->getMessage()], 500);
        }
    }
}
