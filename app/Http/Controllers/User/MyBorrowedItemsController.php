<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BorrowItemInstance;
use App\Models\BorrowRequestItem;
use App\Models\BorrowRequest;
use App\Models\WalkInRequest;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use App\Services\BorrowRequestFormPdf;
use App\Services\RoutingSlipPdf;
use App\Services\WalkInRequestPdfService;
use App\Services\WalkInRoutingSlipPdf;
use App\Services\PhilSmsService;
use App\Notifications\RequestNotification;
use Illuminate\Validation\ValidationException;

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

    public function routingSlip(BorrowRequest $borrowRequest, RoutingSlipPdf $routingSlipPdf)
    {
        if ($borrowRequest->user_id !== Auth::id()) {
            abort(403);
        }

        $deliveryStatus = strtolower((string) $borrowRequest->delivery_status);
        if (! in_array($deliveryStatus, ['delivered', 'returned'], true)) {
            abort(403, 'Routing slip is available only after delivery.');
        }

        $result = $routingSlipPdf->render($borrowRequest);

        return response($result['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $result['filename'] . '"',
            'Content-Length' => strlen($result['content']),
        ]);
    }

    public function routingSlipWalkIn(WalkInRequest $walkInRequest, WalkInRoutingSlipPdf $pdfService)
    {
        if ($walkInRequest->user_id !== Auth::id()) {
            abort(403);
        }

        $result = $pdfService->render($walkInRequest);

        return response($result['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $result['filename'] . '"',
            'Content-Length' => strlen($result['content']),
        ]);
    }

    public function list(Request $request)
    {
        $userId = Auth::id();

        $requests = BorrowRequest::with(['items.item', 'items.manpowerRole'])
            ->where('user_id', $userId)
            ->latest()
            ->get()
            ->map(function ($borrowRequest) {
                $status = $borrowRequest->status === 'qr_verified' ? 'approved' : $borrowRequest->status;

                $items = $borrowRequest->items->map(function ($reqItem) {
                    $displayName = $this->resolveBorrowItemDisplayName($reqItem);

                    return [
                        'borrow_request_item_id' => $reqItem->id,
                        'requested_quantity' => $reqItem->requested_quantity ?? $reqItem->quantity,
                        'approved_quantity' => $reqItem->quantity,
                        'received_quantity' => $reqItem->received_quantity,
                        'quantity' => $reqItem->quantity,
                        'assigned_manpower' => $reqItem->assigned_manpower ?? 0,
                        'manpower_role' => $reqItem->manpower_role
                            ?? optional($reqItem->manpowerRole)->name,
                        'manpower_notes' => $reqItem->manpower_notes ?? null,
                        'is_manpower' => (bool) $reqItem->is_manpower,
                        'display_name' => $displayName,
                        'item' => [
                            'id' => $reqItem->item->id ?? null,
                            'name' => $displayName,
                        ],
                    ];
                })->values();

                $instances = $this->fetchBorrowedInstances($borrowRequest->id);

                $returnProofUrl = $borrowRequest->return_proof_path
                    ? Storage::disk('public')->url($borrowRequest->return_proof_path)
                    : null;

                return [
                    'type' => 'borrow',
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
                    'return_proof_path' => $borrowRequest->return_proof_path,
                    'return_proof_url' => $returnProofUrl,
                    'return_proof_notes' => $borrowRequest->return_proof_notes,
                ];
            });

        $walkIns = WalkInRequest::with(['items.item'])
            ->where('user_id', $userId)
            ->latest()
            ->get()
            ->map(function (WalkInRequest $walkIn) {
                $delivery = strtolower((string) $walkIn->delivery_status);
                $baseStatus = strtolower((string) $walkIn->status);
                $effectiveStatus = in_array($delivery, ['dispatched', 'delivered', 'returned', 'not_received'], true)
                    ? $delivery
                    : $baseStatus;
                $items = $walkIn->items->map(function ($item) use ($walkIn) {
                    return [
                        'borrow_request_item_id' => $item->id,
                        'requested_quantity' => $item->quantity,
                        'approved_quantity' => $item->quantity,
                        'received_quantity' => null,
                        'quantity' => $item->quantity,
                        'assigned_manpower' => 0,
                        'manpower_role' => null,
                        'is_manpower' => false,
                        'display_name' => $item->item?->name ?? ('Item #' . $item->item_id),
                        'item' => [
                            'id' => $item->item_id,
                            'name' => $item->item?->name ?? ('Item #' . $item->item_id),
                        ],
                    ];
                })->values();

                if ($walkIn->manpower_quantity && $walkIn->manpower_quantity > 0) {
                    $items->prepend([
                        'borrow_request_item_id' => null,
                        'requested_quantity' => $walkIn->manpower_quantity,
                        'approved_quantity' => $walkIn->manpower_quantity,
                        'received_quantity' => null,
                        'quantity' => $walkIn->manpower_quantity,
                        'assigned_manpower' => $walkIn->manpower_quantity,
                        'manpower_role' => $walkIn->manpower_role ?? 'Manpower',
                        'is_manpower' => true,
                        'display_name' => ($walkIn->manpower_role ?? 'Manpower') . ' (x' . $walkIn->manpower_quantity . ')',
                        'item' => [
                            'id' => null,
                            'name' => $walkIn->manpower_role ?? 'Manpower',
                        ],
                    ]);
                }

                return [
                    'type' => 'walkin',
                    'id' => $walkIn->id,
                    'formatted_request_id' => $walkIn->formatted_request_id,
                    'borrow_date' => $walkIn->borrowed_at,
                    'return_date' => $walkIn->returned_at,
                    'time_of_usage' => null,
                    'status' => $effectiveStatus,
                    'delivery_status' => $walkIn->delivery_status,
                    'manpower_count' => $walkIn->manpower_quantity,
                    'location' => $walkIn->address,
                    'purpose' => $walkIn->purpose,
                    'items' => $items,
                    'borrowed_instances' => [],
                    'delivered_at' => $walkIn->delivered_at?->toIso8601String(),
                    'return_proof_path' => null,
                    'return_proof_url' => null,
                ];
            });

        // Sort by latest requested first (borrow_date, then return_date, then id)
        $combined = $requests->concat($walkIns)->sortByDesc(function ($row) {
            return $row['borrow_date'] ?? $row['return_date'] ?? $row['id'];
        })->values();

        return response()->json($combined);
    }

    public function show(BorrowRequest $borrowRequest)
    {
        if ($borrowRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $borrowRequest->load(['items.item', 'items.manpowerRole']);

        $items = $borrowRequest->items->map(function ($reqItem) {
            $displayName = $this->resolveBorrowItemDisplayName($reqItem);

            return [
                'borrow_request_item_id' => $reqItem->id,
                'requested_quantity' => $reqItem->requested_quantity ?? $reqItem->quantity,
                'approved_quantity' => $reqItem->quantity,
                'received_quantity' => $reqItem->received_quantity,
                'quantity' => $reqItem->quantity,
                'assigned_manpower' => $reqItem->assigned_manpower ?? 0,
                'manpower_role' => $reqItem->manpower_role
                    ?? optional($reqItem->manpowerRole)->name,
                'manpower_notes' => $reqItem->manpower_notes ?? null,
                'is_manpower' => (bool) $reqItem->is_manpower,
                'display_name' => $displayName,
                'item' => [
                    'id' => $reqItem->item->id ?? null,
                    'name' => $displayName,
                ],
            ];
        })->values();

        $instances = $this->fetchBorrowedInstances($borrowRequest->id);
        $status = $borrowRequest->status === 'qr_verified' ? 'approved' : $borrowRequest->status;

        $returnProofUrl = $borrowRequest->return_proof_path
            ? Storage::disk('public')->url($borrowRequest->return_proof_path)
            : null;

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
            'return_proof_path' => $borrowRequest->return_proof_path,
            'return_proof_url' => $returnProofUrl,
            'return_proof_notes' => $borrowRequest->return_proof_notes,
        ]);
    }

    public function showWalkIn(WalkInRequest $walkInRequest)
    {
        if ($walkInRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $walkInRequest->load(['items.item']);

        $items = $walkInRequest->items->map(function ($item) {
            return [
                'borrow_request_item_id' => $item->id,
                'requested_quantity' => $item->quantity,
                'approved_quantity' => $item->quantity,
                'received_quantity' => null,
                'quantity' => $item->quantity,
                'assigned_manpower' => 0,
                'manpower_role' => null,
                'is_manpower' => false,
                'display_name' => $item->item?->name ?? ('Item #' . $item->item_id),
                'item' => [
                    'id' => $item->item_id,
                    'name' => $item->item?->name ?? ('Item #' . $item->item_id),
                ],
            ];
        })->values();

        if ($walkInRequest->manpower_quantity && $walkInRequest->manpower_quantity > 0) {
            $items->prepend([
                'borrow_request_item_id' => null,
                'requested_quantity' => $walkInRequest->manpower_quantity,
                'approved_quantity' => $walkInRequest->manpower_quantity,
                'received_quantity' => null,
                'quantity' => $walkInRequest->manpower_quantity,
                'assigned_manpower' => $walkInRequest->manpower_quantity,
                'manpower_role' => $walkInRequest->manpower_role ?? 'Manpower',
                'is_manpower' => true,
                'display_name' => ($walkInRequest->manpower_role ?? 'Manpower') . ' (x' . $walkInRequest->manpower_quantity . ')',
                'item' => [
                    'id' => null,
                    'name' => $walkInRequest->manpower_role ?? 'Manpower',
                ],
            ]);
        }

        $delivery = strtolower((string) $walkInRequest->delivery_status);
        $baseStatus = strtolower((string) $walkInRequest->status);
        $effectiveStatus = in_array($delivery, ['dispatched', 'delivered', 'returned', 'not_received'], true)
            ? $delivery
            : $baseStatus;

        return response()->json([
            'type' => 'walkin',
            'id' => $walkInRequest->id,
            'formatted_request_id' => $walkInRequest->formatted_request_id,
            'borrow_date' => $walkInRequest->borrowed_at,
            'return_date' => $walkInRequest->returned_at,
            'time_of_usage' => null,
            'status' => $effectiveStatus,
            'delivery_status' => $walkInRequest->delivery_status,
            'manpower_count' => $walkInRequest->manpower_quantity,
            'location' => $walkInRequest->address,
            'purpose' => $walkInRequest->purpose,
            'items' => $items,
            'borrowed_instances' => [],
            'delivered_at' => $walkInRequest->delivered_at?->toIso8601String(),
            'return_proof_path' => null,
            'return_proof_url' => null,
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

        if (! in_array($borrowRequest->delivery_status, ['dispatched', 'not_received'], true)) {
            return response()->json(['message' => 'Only dispatched requests can be confirmed.'], 422);
        }

        $validated = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.received_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $receivedMap = collect($validated['items'] ?? [])
            ->mapWithKeys(function ($row) {
                $id = isset($row['id']) ? (int) $row['id'] : 0;
                $qty = isset($row['received_quantity']) ? (int) $row['received_quantity'] : 0;
                return $id > 0 ? [$id => $qty] : [];
            });

        DB::beginTransaction();
        try {
            $borrowRequest->load('items.item', 'borrowedInstances.instance');

            if ($borrowRequest->borrowedInstances->isEmpty()) {
                foreach ($borrowRequest->items as $requestItem) {
                    if ($requestItem->is_manpower) {
                        continue;
                    }

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
                if ($requestItem->is_manpower) {
                    continue;
                }

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

                $approvedQty = (int) $requestItem->quantity;
                $receivedQty = $receivedMap->has($requestItem->id)
                    ? (int) $receivedMap->get($requestItem->id)
                    : $approvedQty;

                if ($receivedQty > $approvedQty) {
                    $label = $this->resolveBorrowItemDisplayName($requestItem) ?? 'Item';
                    throw ValidationException::withMessages([
                        'items' => [sprintf('%s cannot exceed the approved quantity (%d).', $label, $approvedQty)],
                    ]);
                }

                $requestItem->received_quantity = $receivedQty;
                $requestItem->save();
            }

            $borrowRequest->delivery_status = 'delivered';
            $borrowRequest->delivered_at = now();
            $borrowRequest->delivery_reported_at = null;
            $borrowRequest->delivery_report_reason = null;
            $borrowRequest->save();

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();
            $errors = $e->errors();
            $first = collect($errors)->flatten()->first();

            return response()->json([
                'message' => $first ?: 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
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

    public function confirmWalkInDelivery(Request $request, WalkInRequest $walkInRequest)
    {
        if ($walkInRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($walkInRequest->delivery_status === 'delivered') {
            return response()->json(['message' => 'Already confirmed delivered.'], 200);
        }

        if (! in_array($walkInRequest->delivery_status, ['dispatched'], true)) {
            return response()->json(['message' => 'Only dispatched requests can be confirmed.'], 422);
        }

        DB::beginTransaction();
        try {
            $walkInRequest->load('items.item');

            foreach ($walkInRequest->items as $walkInItem) {
                $item = $walkInItem->item;
                if (! $item) {
                    throw new \RuntimeException('One of the requested items could not be found.');
                }

                $availableInstances = ItemInstance::where('item_id', $item->id)
                    ->where('status', 'available')
                    ->lockForUpdate()
                    ->limit($walkInItem->quantity)
                    ->get();

                if ($availableInstances->count() < $walkInItem->quantity) {
                    throw new \RuntimeException("Not enough available instances for {$item->name}.");
                }

                foreach ($availableInstances as $instance) {
                    $instance->status = 'borrowed';
                    $instance->save();

                    BorrowItemInstance::create([
                        'borrow_request_id' => null,
                        'item_id' => $walkInItem->item_id,
                        'item_instance_id' => $instance->id,
                        'borrowed_qty' => 1,
                        'walk_in_request_id' => $walkInRequest->id,
                        'checked_out_at' => now(),
                        'expected_return_at' => $walkInRequest->returned_at,
                        'returned_at' => null,
                        'return_condition' => 'pending',
                    ]);
                }

                $item->available_qty = max(0, (int) $item->available_qty - (int) $walkInItem->quantity);
                $item->save();
            }

            $walkInRequest->status = 'delivered';
            $walkInRequest->delivery_status = 'delivered';
            $walkInRequest->delivered_at = now();
            $walkInRequest->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('user.walkin_confirm_delivery_failed', [
                'walk_in_request_id' => $walkInRequest->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to confirm delivery. Please try again.',
            ], 500);
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

    public function reportWalkInNotReceived(Request $request, WalkInRequest $walkInRequest)
    {
        if ($walkInRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:2000',
        ]);

        $reason = $validated['reason'] ?? null;

        DB::beginTransaction();

        try {
            // Roll back delivery so admin can dispatch again
            $walkInRequest->status = 'approved';
            $walkInRequest->delivery_status = 'not_received';
            $walkInRequest->dispatched_at = null;
            $walkInRequest->delivered_at = null;
            $walkInRequest->delivery_report_reason = $reason;
            $walkInRequest->delivery_reported_at = now();
            $walkInRequest->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('user.walkin_report_not_received_failed', [
                'walk_in_request_id' => $walkInRequest->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to submit report. Please try again.',
            ], 500);
        }

        Log::info('user.walkin_report_not_received', [
            'walk_in_request_id' => $walkInRequest->id,
            'user_id' => Auth::id(),
            'reason' => $reason,
        ]);

        // Notify admins similar to regular borrow requests
        $borrowerName = Auth::user()?->name ?? 'Borrower';
        $payload = [
            'type' => 'delivery_reported',
            'message' => sprintf('%s reported a delivery issue for %s.', $borrowerName, $walkInRequest->formatted_request_id ?? ('Walk-in #' . $walkInRequest->id)),
            'borrow_request_id' => null,
            'walk_in_request_id' => $walkInRequest->id,
            'formatted_request_id' => $walkInRequest->formatted_request_id,
            'user_id' => $walkInRequest->user_id,
            'user_name' => $borrowerName,
            'actor_id' => $walkInRequest->user_id,
            'actor_name' => $borrowerName,
            'reason' => $walkInRequest->delivery_report_reason,
            'reported_at' => $walkInRequest->delivery_reported_at?->toDateTimeString(),
        ];

        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new RequestNotification($payload));

        return response()->json(['message' => 'Reported not received.']);
    }

    public function markReturned(Request $request, BorrowRequest $borrowRequest)
    {
        if ($borrowRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $deliveryStatus = strtolower((string) $borrowRequest->delivery_status);
        if (! in_array($deliveryStatus, ['delivered', 'returned'], true)) {
            return response()->json(['message' => 'Only delivered requests can be marked as returned.'], 422);
        }

        $validated = $request->validate([
            'return_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::beginTransaction();

        try {
            if ($borrowRequest->return_proof_path) {
                Storage::disk('public')->delete($borrowRequest->return_proof_path);
            }

            $storedPath = $validated['return_proof']->store('return-proofs', 'public');

            $borrowRequest->return_proof_path = $storedPath;
            $borrowRequest->return_proof_notes = $validated['notes'] ?? null;
            $borrowRequest->status = 'returned';
            $borrowRequest->delivery_status = 'returned';
            if (! $borrowRequest->delivered_at) {
                $borrowRequest->delivered_at = now();
            }
            $borrowRequest->save();

            DB::commit();

            return response()->json([
                'message' => 'Return submitted successfully.',
                'return_proof_path' => $borrowRequest->return_proof_path,
                'return_proof_notes' => $borrowRequest->return_proof_notes,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('user.mark_returned_failed', [
                'borrow_request_id' => $borrowRequest->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to submit return proof. Please try again.',
            ], 500);
        }
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

    private function resolveBorrowItemDisplayName(BorrowRequestItem $item): string
    {
        if (! $item->relationLoaded('item') || ! $item->relationLoaded('manpowerRole')) {
            $item->loadMissing('item', 'manpowerRole');
        }

        $roleName = $item->manpower_role ?? optional($item->manpowerRole)->name;
        $itemName = optional($item->item)->name;

        if ($item->is_manpower || $itemName === Item::SYSTEM_MANPOWER_PLACEHOLDER) {
            return $roleName ?: 'Manpower';
        }

        return $itemName ?: ($roleName ?: 'Unknown');
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
