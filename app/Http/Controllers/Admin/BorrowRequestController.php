<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BorrowRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Events\BorrowRequestStatusUpdated;
use App\Notifications\RequestNotification;
use App\Models\BorrowItemInstance;
use App\Models\BorrowRequestItem;
use App\Models\WalkInRequest;
use App\Services\BorrowRequestFormPdf;
use App\Models\RejectionReason;
use App\Support\StatusRank;
use App\Services\WalkInRequestPdfService;

class BorrowRequestController extends Controller
{
    public function walkInIndex()
    {
        // List existing walk-in requests (using new tables once created) - placeholder empty collection for now
        $requests = \Illuminate\Support\Collection::make();
        return view('admin.walk-in.index', compact('requests'));
    }

    public function walkInCreate()
    {
        $items = \App\Models\Item::orderBy('name')
            ->get(['id','name','category','total_qty','available_qty','photo']);
        $defaultPhoto = 'images/item.png';
        return view('admin.walk-in.create', compact('items','defaultPhoto'));
    }

    public function walkInList()
    {
        $rows = \App\Models\WalkInRequest::query()
            ->with('items.item')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($r) {
                $timezone = config('app.timezone');

                $formatDate = function ($dt) use ($timezone) {
                    return $dt ? $dt->timezone($timezone)->format('M d, Y') : null;
                };

                $formatTime = function ($dt) use ($timezone) {
                    if (! $dt) {
                        return null;
                    }

                    return $dt->format('H:i:s') === '00:00:00'
                        ? null
                        : $dt->timezone($timezone)->format('g:i A');
                };

                $iso = function ($dt) use ($timezone) {
                    return $dt ? $dt->timezone($timezone)->toIso8601String() : null;
                };

                return [
                    'id' => $r->id,
                    'borrower_name' => $r->borrower_name,
                    'office_agency' => $r->office_agency,
                    'contact_number' => $r->contact_number,
                    'address' => $r->address,
                    'purpose' => $r->purpose,
                    'status' => $r->status,
                    'borrowed_at' => $iso($r->borrowed_at),
                    'returned_at' => $iso($r->returned_at),
                    'borrowed_date_display' => $formatDate($r->borrowed_at),
                    'returned_date_display' => $formatDate($r->returned_at),
                    'borrowed_time_display' => $formatTime($r->borrowed_at),
                    'returned_time_display' => $formatTime($r->returned_at),
                    'items' => $r->items->map(function ($ri) {
                        return [
                            'id' => $ri->item_id,
                            'name' => $ri->item?->name,
                            'quantity' => $ri->quantity,
                        ];
                    })->values()->all(),
                ];
            });
        return response()->json($rows);
    }

    public function walkInStore(Request $request)
    {
        $data = $request->validate([
            'borrower_name' => 'required|string|max:255',
            'office_agency' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'purpose' => 'required|string|max:500',
            'borrowed_at' => 'required|date',
            'returned_at' => 'required|date|after_or_equal:borrowed_at',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        \DB::beginTransaction();
        try {
            $walkin = new \App\Models\WalkInRequest();
            $walkin->fill([
                'borrower_name' => $data['borrower_name'],
                'office_agency' => $data['office_agency'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'address' => $data['address'] ?? null,
                'purpose' => $data['purpose'],
                'borrowed_at' => $data['borrowed_at'],
                'returned_at' => $data['returned_at'],
                'status' => 'pending',
                'created_by' => $request->user()->id,
            ]);
            $walkin->save();

            foreach ($data['items'] as $it) {
                \App\Models\WalkInRequestItem::create([
                    'walk_in_request_id' => $walkin->id,
                    'item_id' => $it['id'],
                    'quantity' => $it['quantity'],
                ]);
            }

            \DB::commit();

            return response()->json([
                'message' => 'Walk-in request created successfully.',
                'id' => $walkin->id,
            ], 201);
        } catch (\Throwable $e) {
            \DB::rollBack();
            return response()->json([
                'message' => 'Failed to create walk-in request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function walkInApproveQr(Request $request, $id)
    {
        $walkInRequest = WalkInRequest::findOrFail($id);

        if (!$walkInRequest->isPending()) {
            return view('admin.walk-in.qr-result', [
                'success' => false,
                'message' => 'This request has already been processed.',
                'request' => $walkInRequest,
            ]);
        }

        DB::beginTransaction();
        try {
            $walkInRequest->status = 'approved';
            $walkInRequest->save();

            DB::commit();

            return view('admin.walk-in.qr-result', [
                'success' => true,
                'message' => 'Walk-in request approved successfully!',
                'request' => $walkInRequest,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to approve walk-in request via QR', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return view('admin.walk-in.qr-result', [
                'success' => false,
                'message' => 'Failed to approve the request. Please try again.',
                'request' => $walkInRequest,
            ]);
        }
    }

    public function walkInDeliver(Request $request, $id)
    {
        // Eager load items relationship
        $walkInRequest = WalkInRequest::with('items')->findOrFail($id);

        if (!$walkInRequest->isApproved()) {
            return response()->json([
                'message' => 'Only approved requests can be delivered.',
            ], 422);
        }

        // Check if there are items to deliver
        if ($walkInRequest->items->isEmpty()) {
            return response()->json([
                'message' => 'No items found in this request.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create individual BorrowItemInstance records for each physical item instance
            foreach ($walkInRequest->items as $walkInItem) {
                // Validate item exists
                $item = \App\Models\Item::find($walkInItem->item_id);
                if (!$item) {
                    throw new \Exception("Item #{$walkInItem->item_id} not found.");
                }

                // Check if sufficient quantity available
                if ($item->available_qty < $walkInItem->quantity) {
                    throw new \Exception("Insufficient quantity for {$item->name}. Available: {$item->available_qty}, Requested: {$walkInItem->quantity}");
                }

                // Find available ItemInstance records for this item
                $availableInstances = \App\Models\ItemInstance::where('item_id', $walkInItem->item_id)
                    ->where('status', 'available')
                    ->limit($walkInItem->quantity)
                    ->get();

                // Verify we have enough available instances
                if ($availableInstances->count() < $walkInItem->quantity) {
                    throw new \Exception("Not enough available instances for {$item->name}. Found: {$availableInstances->count()}, Needed: {$walkInItem->quantity}");
                }

                // For each instance, update status and create borrow record
                foreach ($availableInstances as $instance) {
                    // Update ItemInstance status to 'borrowed'
                    $instance->status = 'borrowed';
                    $instance->save();

                    // Create individual BorrowItemInstance record
                    BorrowItemInstance::create([
                        'borrow_request_id' => null, // No associated borrow request
                        'item_id' => $walkInItem->item_id,
                        'item_instance_id' => $instance->id,
                        'borrowed_qty' => 1, // Each record represents 1 physical instance
                        'walk_in_request_id' => $walkInRequest->id,
                        'checked_out_at' => now(),
                        'returned_at' => null, // Not returned yet
                        'return_condition' => 'pending',
                    ]);
                }

                // Deduct from available quantity
                $item->available_qty = max(0, $item->available_qty - $walkInItem->quantity);
                $item->save();
            }

            // Change status to delivered
            $walkInRequest->status = 'delivered';
            $walkInRequest->save();

            DB::commit();

            return response()->json([
                'message' => 'Walk-in request marked as delivered. Items deducted from inventory.',
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to deliver walk-in request', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function walkInPrint(WalkInRequest $walkInRequest, WalkInRequestPdfService $pdfService)
    {
        $walkInRequest->load(['items.item']);

        $result = $pdfService->render($walkInRequest);
        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'] ?? 'Failed to generate walk-in request PDF.',
            ], 500);
        }

        return response()->streamDownload(function () use ($result) {
            echo $result['content'];
        }, $result['filename'], [
            'Content-Type' => $result['mime'],
        ]);
    }
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

        // Use centralized status rank map

        // Guard against nonsensical backward changes when delivery has progressed
        // If items are already delivered, only allow transition to return stages
        if ($borrowRequest->delivery_status === 'delivered' && ! in_array($new, ['returned', 'return_pending'], true)) {
            return response()->json([
                'message' => 'Cannot modify request status after delivery except to handle returns.',
            ], 422);
        }

        // Generic downgrade prevention once dispatched: can't reduce rank below approved
        if ($borrowRequest->delivery_status === 'dispatched') {
            $newRank = StatusRank::rank($new);
            $approvedRank = StatusRank::rank('approved');
            if ($newRank !== -1 && $newRank < $approvedRank && !in_array($new, ['return_pending','returned'], true)) {
                return response()->json([
                    'message' => 'Cannot downgrade status after dispatch.',
                ], 422);
            }
        }

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
            // Log full exception so we can see stack trace in storage/logs/laravel.log
            Log::error('Failed to update borrow request status', [
                'borrow_request_id' => $borrowRequest->id ?? null,
                'old_status' => $old ?? null,
                'new_status' => $new ?? null,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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
        // Assumes $borrowRequest->load('items.item') has been called by caller if needed.
        foreach ($borrowRequest->items as $requestItem) {
            $item = $requestItem->item;
            if (!$item) {
                continue;
            }

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
        // Normalize status if QR-verified
        if ($borrowRequest->status === 'qr_verified') {
            $borrowRequest->status = 'approved';
            $borrowRequest->save();
        }

        // Only validated or approved can proceed
        if ($borrowRequest->status !== 'validated' && $borrowRequest->status !== 'approved') {
            return response()->json(['message' => 'Only validated or approved requests can be dispatched.'], 422);
        }

        // Idempotent: already dispatched
        if ($borrowRequest->delivery_status === 'dispatched') {
            return response()->json(['message' => 'Already dispatched.'], 200);
        }

        // If already delivered (legacy direct delivery), treat as dispatched
        if ($borrowRequest->delivery_status === 'delivered') {
            return response()->json(['message' => 'Already delivered.'], 200);
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
                foreach ($borrowRequest->items as $requestItem) {
                    $item = $requestItem->item;
                    if (!$item) continue;

                    $totalQty = (int) ($item->total_qty ?? 0);
                    $availableQty = (int) ($item->available_qty ?? 0);

                    if ($totalQty === 0) continue;
                    $percentage = ($totalQty > 0) ? (($availableQty / $totalQty) * 100) : 0;
                    if ($percentage < 98 || $availableQty === 0) {
                        DB::rollBack();
                        return response()->json(['message' => 'Failed to dispatch.'], 422);
                    }
                }
                // allocate (instances marked allocated, no stock deduction yet)
                $this->allocateInstancesForBorrowRequest($borrowRequest);
            }

            // set approved status and delivery meta (dispatch step only)
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

            // notify user - dispatched only (two-step flow)
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
        // If already delivered, make idempotent for legacy callers
        if ($borrowRequest->delivery_status === 'delivered') {
            return response()->json(['message' => 'Already delivered.'], 200);
        }

        if ($borrowRequest->delivery_status !== 'dispatched') {
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

                // Now deduct from available quantity
                $item->available_qty = max(0, (int) $item->available_qty - $needed);
                $item->save();
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

    /**
     * Cancel a dispatch prior to delivery. Reverts delivery_status and deallocates instances.
     * Restores instance statuses to 'available' and leaves borrow request in 'approved' (or 'validated' if it was never approved before dispatch logic).
     */
    public function cancelDispatch(Request $request, BorrowRequest $borrowRequest)
    {
        if ($borrowRequest->delivery_status !== 'dispatched') {
            return response()->json(['message' => 'Only dispatched requests can be canceled.'], 422);
        }

        DB::beginTransaction();
        try {
            // Load allocated instances
            $borrowRequest->load('borrowedInstances.instance', 'items.item');

            // Revert each allocated instance back to available and remove BorrowItemInstance rows
            $allocRows = $borrowRequest->borrowedInstances()->get();
            foreach ($allocRows as $row) {
                if ($row->instance && $row->instance->status === 'allocated') {
                    $row->instance->status = 'available';
                    $row->instance->save();
                }
                $row->delete();
            }

            // Reset delivery fields
            $borrowRequest->delivery_status = null;
            $borrowRequest->dispatched_at = null;
            // Keep status at approved (we don't automatically downgrade to validated to avoid losing adjustments)
            $borrowRequest->save();

            // Notify user about cancellation
            $user = $borrowRequest->user;
            if ($user) {
                $payload = [
                    'type' => 'borrow_dispatch_canceled',
                    'message' => "Dispatch for borrow request #{$borrowRequest->id} has been canceled.",
                    'borrow_request_id' => $borrowRequest->id,
                ];
                $user->notify(new RequestNotification($payload));
            }

            DB::commit();
            return response()->json(['message' => 'Dispatch canceled and allocations rolled back.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to cancel dispatch. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
