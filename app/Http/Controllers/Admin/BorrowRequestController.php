<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BorrowRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            ->get()
            ->map(function (BorrowRequest $request) {
                return [
                    'id' => $request->id,
                    'borrow_date' => $request->borrow_date,
                    'return_date' => $request->return_date,
                    'status' => $request->status,
                    'delivery_status' => $request->delivery_status,
                    'manpower_count' => $request->manpower_count,
                    'manpower_adjustment_reason' => $request->manpower_adjustment_reason,
                    'location' => $request->location,
                    'letter_path' => $request->letter_path,
                    'letter_url' => $this->makeLetterUrl($request->letter_path),
                    'user' => $request->user ? [
                        'id' => $request->user->id,
                        'first_name' => $request->user->first_name,
                        'last_name' => $request->user->last_name,
                    ] : null,
                    'items' => $request->items->map(function (BorrowRequestItem $item) {
                        return [
                            'id' => $item->id,
                            'borrow_request_item_id' => $item->id,
                            'item_id' => $item->item_id,
                            'quantity' => $item->quantity,
                            'quantity_reason' => $item->manpower_notes,
                            'item' => $item->item ? [
                                'id' => $item->item->id,
                                'name' => $item->item->name,
                            ] : null,
                        ];
                    })->values(),
                ];
            });

        return response()->json($requests);
    }

    public function updateStatus(Request $request, BorrowRequest $borrowRequest)
    {
        $request->validate([
            'status' => 'required|in:pending,validated,approved,rejected,returned,return_pending,qr_verified'
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

            if ($new === 'validated') {
                $assignments = $request->input('manpower_assignments', []);

                if ($assignments && is_array($assignments)) {
                    foreach ($assignments as $assignment) {
                        $briId = isset($assignment['borrow_request_item_id']) ? (int) $assignment['borrow_request_item_id'] : null;

                        if (! $briId) {
                            DB::rollBack();
                            return response()->json(['message' => 'Invalid manpower assignment row.'], 422);
                        }

                        $bri = BorrowRequestItem::where('id', $briId)
                            ->where('borrow_request_id', $borrowRequest->id)
                            ->first();

                        if (! $bri) {
                            DB::rollBack();
                            return response()->json(['message' => 'Invalid manpower assignment row.'], 422);
                        }

                        $origQty = (int) $bri->quantity;
                        $newQty = isset($assignment['quantity']) ? (int) $assignment['quantity'] : $origQty;

                        if ($newQty < 0) {
                            DB::rollBack();
                            return response()->json(['message' => 'Invalid quantity provided.'], 422);
                        }

                        if ($newQty > $origQty) {
                            $newQty = $origQty;
                        }

                        $bri->quantity = $newQty;
                        $bri->assigned_manpower = null;
                        $bri->manpower_role = null;
                        $bri->manpower_notes = isset($assignment['quantity_reason']) && $assignment['quantity_reason'] !== ''
                            ? substr($assignment['quantity_reason'], 0, 255)
                            : null;
                        $bri->assigned_by = \Illuminate\Support\Facades\Auth::id();
                        $bri->assigned_at = now();
                        $bri->save();
                    }
                }

                if ($request->filled('manpower_total')) {
                    $borrowRequest->manpower_count = max(0, (int) $request->input('manpower_total'));
                    $borrowRequest->manpower_adjustment_reason = $request->input('manpower_reason')
                        ? substr($request->input('manpower_reason'), 0, 255)
                        : null;
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
                            'return_condition'  => 'pending',
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
                    ];

                    $user->notify(new RequestNotification($payload));
                }
            } catch (\Throwable $e) {
            }

            $response = [
                'message' => 'Status updated successfully',
                'status'  => $borrowRequest->status
            ];

            return response()->json($response);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update status',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function scan(Request $request, BorrowRequest $borrowRequest)
    {
        $oldStatus = $borrowRequest->status ?? 'pending';
        $wasUpdated = false;

        if ($borrowRequest->status !== 'qr_verified') {
            $borrowRequest->status = 'qr_verified';
            $borrowRequest->save();

            event(new BorrowRequestStatusUpdated($borrowRequest->fresh(), $oldStatus, $borrowRequest->status));
            $borrowRequest->refresh();
            $wasUpdated = true;
        }

        $message = $wasUpdated
            ? 'Borrow request marked as QR verified.'
            : 'Borrow request was already marked as QR verified.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'status' => $borrowRequest->status,
                'updated' => $wasUpdated,
            ]);
        }

        return view('admin.borrow-requests.scan-result', [
            'borrowRequest' => $borrowRequest,
            'updated' => $wasUpdated,
            'message' => $message,
        ]);
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
                    'return_condition'  => 'pending',
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

    private function makeLetterUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return null;
    }
}
