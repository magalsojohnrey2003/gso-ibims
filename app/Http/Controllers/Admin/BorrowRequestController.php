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
use App\Services\BorrowRequestFormPdf;
use App\Models\RejectionReason;
use Illuminate\Support\Facades\Log;

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
                $status = $request->status === 'qr_verified' ? 'approved' : $request->status;
                return [
                    'id' => $request->id,
                    'borrow_date' => $request->borrow_date,
                    'return_date' => $request->return_date,
                    'status' => $status,
                    'delivery_status' => $request->delivery_status,
                    'rejection_reason_id' => $request->rejection_reason_id,
                    'reject_category' => $request->reject_category,
                    'reject_reason' => $request->reject_reason,
                    'manpower_count' => $request->manpower_count,
                    'manpower_adjustment_reason' => $request->manpower_adjustment_reason,
                    'location' => $request->location,
                    'letter_path' => $request->letter_path,
                    'letter_url' => $this->makeLetterUrl($request->letter_path),
                    'qr_verified_form_path' => $request->qr_verified_form_path,
                    'qr_verified_form_url' => $this->makeLetterUrl($request->qr_verified_form_path),
                    'approved_form_url' => $this->makeLetterUrl($request->qr_verified_form_path),
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
                                'available_qty' => $item->item->available_qty ?? 0,
                                'total_qty' => $item->item->total_qty ?? 0,
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
        $new = $request->status === 'qr_verified' ? 'approved' : $request->status;

        if ($old === $new) {
            return response()->json([
                'message' => 'No change',
                'status'  => $borrowRequest->status
            ]);
        }

        $notificationReason = null;

        $requestedReasonId = $request->input('reject_reason_id');
        $inputRejectSubject = $request->input('reject_subject');
        $inputRejectDetail = $request->input('reject_detail');

        DB::beginTransaction();
        try {
            $borrowRequest->load('items.item');

            if ($new === 'rejected') {
                $resolvedTemplate = null;
                if ($requestedReasonId) {
                    $resolvedTemplate = RejectionReason::query()
                        ->where('id', $requestedReasonId)
                        ->lockForUpdate()
                        ->first();

                    if (! $resolvedTemplate) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'The selected rejection reason is no longer available.',
                        ], 422);
                    }
                }

                $subject = is_string($inputRejectSubject) ? trim($inputRejectSubject) : '';
                $detail = is_string($inputRejectDetail) ? trim($inputRejectDetail) : '';

                if ($resolvedTemplate) {
                    $subject = $resolvedTemplate->subject;
                    $detail = $resolvedTemplate->detail;
                }

                if ($subject === '' || $detail === '') {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'A rejection subject and detailed explanation are required.',
                    ], 422);
                }

                $borrowRequest->rejection_reason_id = $resolvedTemplate?->id;
                $borrowRequest->reject_category = $subject;
                $borrowRequest->reject_reason = $detail;
                $notificationReason = $detail;

                if ($resolvedTemplate) {
                    $resolvedTemplate->usage_count = ($resolvedTemplate->usage_count ?? 0) + 1;
                    $resolvedTemplate->save();
                }
            }

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
                Log::info('Processing approval status change', [
                    'borrow_request_id' => $borrowRequest->id,
                    'old_status' => $old,
                    'new_status' => $new
                ]);
                
                foreach ($borrowRequest->items as $reqItem) {
                    $item = $reqItem->item;
                    if (! $item) {
                        Log::error('Item not found during approval', [
                            'borrow_request_id' => $borrowRequest->id,
                            'request_item_id' => $reqItem->id
                        ]);
                        DB::rollBack();
                        return response()->json(['message' => 'Item not found for a request row.'], 422);
                    }
                    
                    Log::info('Processing item for approval', [
                        'item_id' => $item->id,
                        'item_name' => $item->name,
                        'current_available_qty' => $item->available_qty
                    ]);

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
                        $inst->status = 'allocated';
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
                    // Stock deduction will happen on delivery
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
                        'reason' => $notificationReason ?? $borrowRequest->reject_reason,
                        'reject_category' => $borrowRequest->reject_category,
                    ];

                    $user->notify(new RequestNotification($payload));
                }
            } catch (\Throwable $e) {
            }

            $statusMessages = [
                'pending' => 'Request status set to pending.',
                'validated' => 'Request validated successfully.',
                'approved' => 'Request approved successfully.',
                'rejected' => 'Request rejected.',
                'returned' => 'Request marked as returned.',
                'return_pending' => 'Request marked as return pending.',
            ];
            
            $response = [
                'message' => $statusMessages[$new] ?? 'Status updated successfully.',
                'status'  => $borrowRequest->status,
                'rejection_reason_id' => $borrowRequest->rejection_reason_id,
                'reject_category' => $borrowRequest->reject_category,
                'reject_reason' => $borrowRequest->reject_reason,
            ];

            return response()->json($response);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update request status. Please try again.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function scan(Request $request, BorrowRequest $borrowRequest, BorrowRequestFormPdf $formPdf)
    {
        $oldStatus = $borrowRequest->status ?? 'pending';
        $wasUpdated = false;

        if ($borrowRequest->status !== 'approved') {
            $borrowRequest->status = 'approved';
            $borrowRequest->save();

            event(new BorrowRequestStatusUpdated($borrowRequest->fresh(), $oldStatus, $borrowRequest->status));
            $borrowRequest->refresh();
            $wasUpdated = true;
        }

        $message = $wasUpdated
            ? 'Borrow request marked as Approved via QR scan.'
            : 'Borrow request was already marked as Approved.';

        $downloadUrl = null;

        try {
            $result = $formPdf->render($borrowRequest);
            $timestamp = now()->format('YmdHis');
            $path = "qr-verified-forms/request-{$borrowRequest->id}/borrow-request-{$borrowRequest->id}-{$timestamp}.pdf";
            $disk = Storage::disk('public');

            if ($borrowRequest->qr_verified_form_path && $disk->exists($borrowRequest->qr_verified_form_path)) {
                $disk->delete($borrowRequest->qr_verified_form_path);
            }

            $disk->put($path, $result['content']);

            $borrowRequest->qr_verified_form_path = $path;
            $borrowRequest->save();

            $downloadUrl = $this->makeLetterUrl($path);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'status' => $borrowRequest->status,
                'updated' => $wasUpdated,
                'qr_verified_form_url' => $downloadUrl,
                'approved_form_url' => $downloadUrl,
            ]);
        }

        return view('admin.borrow-requests.scan-result', [
            'borrowRequest' => $borrowRequest,
            'updated' => $wasUpdated,
            'message' => $message,
            'downloadUrl' => $downloadUrl,
        ]);
    }

    protected function allocateInstancesForBorrowRequest(BorrowRequest $borrowRequest): void
    {
        Log::info('Starting allocation for borrow request', [
            'borrow_request_id' => $borrowRequest->id,
            'status' => $borrowRequest->status
        ]);
        
        // Assumes $borrowRequest->load('items.item') has been called by caller if needed.
        foreach ($borrowRequest->items as $requestItem) {
            $item = $requestItem->item;
            if (!$item) {
                continue;
            }
            
            Log::info('Processing item for allocation', [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'current_available_qty' => $item->available_qty
            ]);

            $needed = $requestItem->quantity;
            $instances = $item->instances()
                ->where('status', 'available')
                ->take($needed)
                ->get();

            foreach ($instances as $inst) {
                $inst->status = 'allocated';  // Changed from 'borrowed' to 'allocated'
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
            // Stock deduction happens on delivery, not allocation
        }
    }

    public function dispatch(Request $request, BorrowRequest $borrowRequest)
    {
        if ($borrowRequest->status === 'qr_verified') {
            $borrowRequest->status = 'approved';
            $borrowRequest->save();
        }

        if ($borrowRequest->status !== 'validated' && $borrowRequest->status !== 'approved') {
            return response()->json(['message' => 'Only validated or approved requests can be dispatched.'], 422);
        }

        if ($borrowRequest->delivery_status === 'dispatched') {
            return response()->json(['message' => 'Already dispatched.'], 200);
        }

        // Validate delivery reason if provided
        $data = $request->validate([
            'delivery_reason_type' => 'nullable|in:missing,damaged,others',
            'delivery_reason_subject' => 'nullable|string|max:255|required_if:delivery_reason_type,others',
            'delivery_reason_explanation' => 'nullable|string|required_if:delivery_reason_type,others',
        ]);

        DB::beginTransaction();
        try {
            // ensure we have items loaded
            $borrowRequest->load('items.item');

            // Only check available quantity if status is not already approved
            // If already approved, items are already allocated, so we can proceed
            if ($borrowRequest->status !== 'approved') {
                // Validate that available quantity is at least 98% of total quantity for all items
                foreach ($borrowRequest->items as $requestItem) {
                    $item = $requestItem->item;
                    if (!$item) {
                        continue;
                    }

                    $totalQty = (int) ($item->total_qty ?? 0);
                    $availableQty = (int) ($item->available_qty ?? 0);

                    // If total quantity is 0, skip check for this item
                    if ($totalQty === 0) {
                        continue;
                    }

                    // Check if available quantity is below 98% threshold
                    $percentage = ($totalQty > 0) ? (($availableQty / $totalQty) * 100) : 0;
                    if ($percentage < 98 || $availableQty === 0) {
                        DB::rollBack();
                        return response()->json(['message' => 'Failed to dispatch.'], 422);
                    }
                }

                // allocate item instances if status is not already approved (i.e. not allocated)
                $this->allocateInstancesForBorrowRequest($borrowRequest);
            }

            // set approved status and delivery meta
            $borrowRequest->status = 'approved';
            $borrowRequest->delivery_status = 'dispatched';
            $borrowRequest->dispatched_at = now();
            
            // Store delivery reason
            if (!empty($data['delivery_reason_type'])) {
                $borrowRequest->delivery_reason_type = $data['delivery_reason_type'];
                
                // If "others" is selected, store subject and explanation as JSON
                if ($data['delivery_reason_type'] === 'others') {
                    $borrowRequest->delivery_reason_details = json_encode([
                        'subject' => $data['delivery_reason_subject'] ?? '',
                        'explanation' => $data['delivery_reason_explanation'] ?? '',
                    ]);
                } else {
                    $borrowRequest->delivery_reason_details = null;
                }
            }
            
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
            return response()->json(['message' => 'Items dispatched successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to dispatch items. Please try again.', 'error' => $e->getMessage()], 500);
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

    public function markDelivered(Request $request, BorrowRequest $borrowRequest)
    {
        Log::info('Starting markDelivered process', [
            'borrow_request_id' => $borrowRequest->id,
            'current_status' => $borrowRequest->status,
            'current_delivery_status' => $borrowRequest->delivery_status
        ]);

        if ($borrowRequest->delivery_status !== 'dispatched') {
            Log::warning('Attempted to mark non-dispatched request as delivered', [
                'borrow_request_id' => $borrowRequest->id,
                'current_delivery_status' => $borrowRequest->delivery_status
            ]);
            return response()->json(['message' => 'Only dispatched items can be marked as delivered.'], 422);
        }

        DB::beginTransaction();
        try {
            $borrowRequest->load('items.item', 'borrowedInstances.instance');

            // Update statuses from 'allocated' to 'borrowed' and deduct stock
            foreach ($borrowRequest->items as $requestItem) {
                $item = $requestItem->item;
                if (!$item) {
                    continue;
                }

                $needed = $requestItem->quantity;

                // Update the instances status
                $borrowRequest->borrowedInstances()
                    ->whereHas('instance', function ($query) {
                        $query->where('status', 'allocated');
                    })
                    ->where('item_id', $item->id)
                    ->get()
                    ->each(function ($borrowInstance) {
                        if ($borrowInstance->instance) {
                            $borrowInstance->instance->status = 'borrowed';
                            $borrowInstance->instance->save();
                        }
                    });

                // Log before deducting quantity
                Log::info('About to deduct stock on delivery', [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'current_qty' => $item->available_qty,
                    'deduct_amount' => $needed,
                    'new_qty' => max(0, (int) $item->available_qty - $needed)
                ]);

                // Now deduct from available quantity
                $item->available_qty = max(0, (int) $item->available_qty - $needed);
                $item->save();

                Log::info('Stock deducted on delivery', [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'new_qty' => $item->available_qty
                ]);
            }

            $borrowRequest->delivery_status = 'delivered';
            $borrowRequest->delivered_at = now();
            $borrowRequest->save();

            // Notify the user
            $user = $borrowRequest->user;
            if ($user) {
                $payload = [
                    'type' => 'borrow_delivered',
                    'message' => "Your borrow request #{$borrowRequest->id} has been delivered.",
                    'borrow_request_id' => $borrowRequest->id,
                    'user_id' => $user->id,
                    'delivered_at' => $borrowRequest->delivered_at?->toDateTimeString(),
                ];
                $user->notify(new RequestNotification($payload));
            }

            DB::commit();
            return response()->json(['message' => 'Items marked as delivered successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to mark items as delivered. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
