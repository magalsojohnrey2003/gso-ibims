<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BorrowRequest;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Events\BorrowRequestStatusUpdated;
use App\Notifications\RequestNotification;
use App\Models\BorrowItemInstance;
use App\Models\BorrowRequestItem;
use App\Models\ManpowerRole;
use App\Models\WalkInRequest;
use App\Services\BorrowRequestFormPdf;
use App\Services\PhilSmsService;
use App\Services\PropertyNumberService;
use App\Services\StickerPdfService;
use App\Models\RejectionReason;
use App\Support\StatusRank;
use App\Services\WalkInRequestPdfService;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

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
        $items = Item::query()
            ->excludeSystemPlaceholder()
            ->orderBy('name')
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

                $delivery = strtolower((string) $r->delivery_status);
                $status = strtolower((string) $r->status);
                $effectiveStatus = in_array($delivery, ['dispatched', 'delivered', 'returned', 'not_received'], true)
                    ? $delivery
                    : $status;

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

                $receivedTotal = (int) $r->items->sum(function ($ri) {
                    return (int) ($ri->received_quantity ?? 0);
                });
                $approvedTotal = (int) $r->items->sum(function ($ri) {
                    return (int) ($ri->quantity ?? 0);
                });

                return [
                    'id' => $r->id,
                    'formatted_request_id' => $r->formatted_request_id,
                    'borrower_name' => $r->borrower_name,
                    'office_agency' => $r->office_agency,
                    'contact_number' => $r->contact_number,
                    'address' => $r->address,
                    'purpose' => $r->purpose,
                    'status' => $effectiveStatus,
                    'borrowed_at' => $iso($r->borrowed_at),
                    'returned_at' => $iso($r->returned_at),
                    'delivery_status' => $r->delivery_status,
                    'dispatched_at' => $iso($r->dispatched_at),
                    'delivered_at' => $iso($r->delivered_at),
                    'delivery_report_reason' => $r->delivery_report_reason,
                    'delivery_reported_at' => $iso($r->delivery_reported_at),
                    'manpower_role' => $r->manpower_role,
                    'manpower_quantity' => $r->manpower_quantity,
                    'user_id' => $r->user_id,
                    'borrowed_date_display' => $formatDate($r->borrowed_at),
                    'returned_date_display' => $formatDate($r->returned_at),
                    'borrowed_time_display' => $formatTime($r->borrowed_at),
                    'returned_time_display' => $formatTime($r->returned_at),
                    'received_total' => $receivedTotal,
                    'approved_total' => $approvedTotal,
                    'items' => $r->items->map(function ($ri) {
                        return [
                            'id' => $ri->item_id,
                            'name' => $ri->item?->name,
                            'quantity' => $ri->quantity,
                            'received_quantity' => $ri->received_quantity,
                        ];
                    })->values()->all(),
                ];
            });
        return response()->json($rows);
    }

    public function walkInBorrowers(Request $request)
    {
        $roleFilter = function ($query) {
            $query->where('role', 'user')
                ->orWhereHas('roles', function ($nested) {
                    $nested->where('name', 'user');
                });
        };

        $query = User::query()
            ->select([
                'id',
                'first_name',
                'middle_name',
                'last_name',
                'email',
                'phone',
                'address',
                'borrowing_status',
                'created_at',
            ])
            ->where($roleFilter)
            ->withCount([
                'damageIncidents as damage_incidents_count',
                'borrowRequests as borrow_requests_count',
            ])
            ->withMax('borrowRequests as latest_borrow_request_at', 'created_at');

        if ($search = trim((string) $request->get('q', ''))) {
            $digitsOnly = preg_replace('/\D+/', '', $search);

            $query->where(function ($or) use ($search, $digitsOnly) {
                $or->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

                if ($digitsOnly !== '') {
                    $or->orWhere('phone', 'like', "%{$digitsOnly}%");
                }
            });
        }

        $limit = (int) $request->integer('limit', 25);
        $limit = max(1, min($limit, 100));

        $users = $query
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit($limit)
            ->get();

        $timezone = config('app.timezone');

        $data = $users->map(function (User $user) use ($timezone) {
            $hasActiveBorrow = BorrowRequest::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->whereNull('delivery_status')
                        ->orWhereNotIn('delivery_status', ['returned']);
                })
                ->exists();

            $hasActiveWalkIn = WalkInRequest::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->whereNull('delivery_status')
                        ->orWhereNotIn('delivery_status', ['returned']);
                })
                ->exists();

            $latestBorrowAt = $user->latest_borrow_request_at
                ? Carbon::parse($user->latest_borrow_request_at)->timezone($timezone)
                : null;

            return [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'borrowing_status' => $user->borrowing_status,
                'borrowing_status_label' => $user->borrowing_status_label,
                'damage_incidents_count' => (int) ($user->damage_incidents_count ?? 0),
                'borrow_requests_count' => (int) ($user->borrow_requests_count ?? 0),
                'latest_borrow_request_at' => $latestBorrowAt?->toIso8601String(),
                'latest_borrow_request_display' => $latestBorrowAt?->format('M d, Y') ?? null,
                'registered_at' => $user->created_at?->timezone($timezone)->toIso8601String(),
                'registered_at_display' => $user->created_at?->timezone($timezone)->format('M d, Y'),
                'has_active_borrow' => $hasActiveBorrow || $hasActiveWalkIn,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    public function walkInStore(Request $request)
    {
        $placeholderId = Item::systemPlaceholderId();

        $rules = [
            'borrower_id' => ['nullable','integer','exists:users,id'],
            'borrower_name' => 'required|string|max:255',
            'office_agency' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'purpose' => 'required|string|max:500',
            'borrowed_at' => 'required|date',
            'returned_at' => 'required|date|after_or_equal:borrowed_at',
            'items' => 'required|array|min:1',
            'items.*.id' => ['required','integer','exists:items,id'],
            'items.*.quantity' => 'required|integer|min:1',
            'manpower' => 'required|array',
            'manpower.role' => 'nullable|string|max:255',
            'manpower.quantity' => 'nullable|integer|min:1',
            'manpower.entries' => 'nullable|array',
            'manpower.entries.*.role' => 'required_with:manpower.entries|string|max:255',
            'manpower.entries.*.quantity' => 'required_with:manpower.entries|integer|min:1',
        ];

        if ($placeholderId) {
            $rules['items.*.id'][] = Rule::notIn([$placeholderId]);
        }

        $data = $request->validate($rules);
        \DB::beginTransaction();
        try {
            $entries = collect($data['manpower']['entries'] ?? [])
                ->map(function ($row) {
                    $role = trim((string) ($row['role'] ?? ''));
                    $qty = max(1, (int) ($row['quantity'] ?? 0));
                    return $role !== '' ? ['role' => $role, 'quantity' => $qty] : null;
                })
                ->filter()
                ->values();

            if ($entries->isEmpty() && isset($data['manpower']['role'])) {
                $role = trim((string) $data['manpower']['role']);
                $qty = max(1, (int) ($data['manpower']['quantity'] ?? 0));
                if ($role !== '') {
                    $entries = collect([[
                        'role' => $role,
                        'quantity' => $qty ?: 10,
                    ]]);
                }
            }

            $manpowerSummary = $entries->map(function ($row) {
                return sprintf('%s (x%d)', $row['role'], $row['quantity']);
            })->implode('; ');

            $manpowerTotalQty = $entries->sum('quantity');

            $walkin = new \App\Models\WalkInRequest();
            $walkin->fill([
                'user_id' => $data['borrower_id'] ?? null,
                'borrower_name' => $data['borrower_name'],
                'office_agency' => $data['office_agency'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'address' => $data['address'] ?? null,
                'purpose' => $data['purpose'],
                'borrowed_at' => $data['borrowed_at'],
                'returned_at' => $data['returned_at'],
                'status' => 'pending',
                'delivery_status' => 'pending',
                'manpower_role' => $manpowerSummary ?: ($data['manpower']['role'] ?? 'Assist'),
                'manpower_quantity' => $manpowerTotalQty ?: ($data['manpower']['quantity'] ?? 10),
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

        $scanTimestamp = now();

        if (!$walkInRequest->isPending()) {
            return view('admin.walk-in.qr-result', [
                'success' => false,
                'message' => 'This request has already been processed.',
                'request' => $walkInRequest,
                'scanTimestamp' => $scanTimestamp,
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
                'scanTimestamp' => $scanTimestamp,
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
                'scanTimestamp' => $scanTimestamp,
            ]);
        }
    }

    public function walkInDeliver(Request $request, $id)
    {
        $walkInRequest = WalkInRequest::with('items')->findOrFail($id);

        if (!$walkInRequest->isApproved()) {
            return response()->json([
                'message' => 'Only approved requests can be dispatched.',
            ], 422);
        }

        if ($walkInRequest->items->isEmpty()) {
            return response()->json([
                'message' => 'No items found in this request.',
            ], 422);
        }

        if ($walkInRequest->delivery_status === 'dispatched') {
            return response()->json([
                'message' => 'Request already dispatched.',
            ], 200);
        }

        DB::beginTransaction();
        try {
            // Keep status at approved; use delivery_status to track dispatch
            $walkInRequest->delivery_status = 'dispatched';
            $walkInRequest->dispatched_at = now();
            $walkInRequest->save();

            DB::commit();

            return response()->json([
                'message' => 'Walk-in request dispatched. Confirm delivery to deduct inventory.',
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to dispatch walk-in request', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function walkInConfirmDelivery(Request $request, $id)
    {
        $walkInRequest = WalkInRequest::with('items')->findOrFail($id);

        if (!in_array($walkInRequest->delivery_status, ['dispatched'], true)) {
            return response()->json([
                'message' => 'Delivery already confirmed.',
            ], 200);
        }

        if ($walkInRequest->items->isEmpty()) {
            return response()->json([
                'message' => 'No items found in this request.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($walkInRequest->items as $walkInItem) {
                $item = Item::lockForUpdate()->find($walkInItem->item_id);
                if (!$item) {
                    throw new \RuntimeException("Item #{$walkInItem->item_id} not found.");
                }

                // Default confirmed quantity to approved quantity when admin marks delivered
                $walkInItem->received_quantity = $walkInItem->received_quantity ?? $walkInItem->quantity;
                $walkInItem->save();

                $availableInstances = \App\Models\ItemInstance::where('item_id', $walkInItem->item_id)
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

            return response()->json([
                'message' => 'Walk-in delivery confirmed and inventory deducted.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('walkin.confirm_delivery_failed', [
                'id' => $id,
                'error' => $e->getMessage(),
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
        $incidentStatuses = ['damage', 'damaged', 'minor_damage', 'missing'];

        $requests = BorrowRequest::with([
                'user' => function ($query) use ($incidentStatuses) {
                    $query->withCount([
                        'damageIncidents as damage_incidents_count' => function ($countQuery) use ($incidentStatuses) {
                            $countQuery->whereIn('return_condition', $incidentStatuses);
                        },
                    ])->withMax([
                        'damageIncidents as latest_damage_incident_at' => function ($maxQuery) use ($incidentStatuses) {
                            $maxQuery->whereIn('return_condition', $incidentStatuses);
                        },
                    ], 'condition_updated_at');
                },
                'items.item',
                'items.manpowerRole',
            ])
            ->latest()
            ->get()
            ->map(function (BorrowRequest $request) {
                $status = $request->status === 'qr_verified' ? 'approved' : $request->status;
                $user = $request->user;
                $timezone = config('app.timezone');
                $latestDamageAt = null;

                if ($user && $user->latest_damage_incident_at) {
                    try {
                        $latestDamageAt = Carbon::parse($user->latest_damage_incident_at)->timezone($timezone)->toIso8601String();
                    } catch (\Throwable $exception) {
                        $latestDamageAt = null;
                    }
                }

                return [
                    'id' => $request->id,
                    'formatted_request_id' => $request->formatted_request_id,
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
                    'user' => $user ? [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'full_name' => $user->full_name,
                        'borrowing_status' => $user->borrowing_status ?? 'good',
                        'borrowing_status_label' => $user->borrowing_status_label,
                        'damage_incidents_count' => (int) ($user->damage_incidents_count ?? 0),
                        'latest_damage_incident_at' => $latestDamageAt,
                        'damage_history_url' => route('admin.users.damage-history', $user),
                    ] : null,
                    'items' => $request->items->map(function (BorrowRequestItem $item) {
                        $displayName = $this->resolveBorrowItemDisplayName($item);
                        $itemModel = $item->item;

                        return [
                            'id' => $item->id,
                            'borrow_request_item_id' => $item->id,
                            'item_id' => $item->item_id,
                            'quantity' => $item->quantity,
                            'approved_quantity' => $item->quantity,
                            'requested_quantity' => $item->requested_quantity ?? $item->quantity,
                            'received_quantity' => $item->received_quantity,
                            'quantity_reason' => $item->manpower_notes,
                            'is_manpower' => (bool) $item->is_manpower,
                            'manpower_role' => $item->manpower_role,
                            'manpower_role_id' => $item->manpower_role_id,
                            'display_name' => $displayName,
                            'item' => $itemModel ? [
                                'id' => $itemModel->id,
                                'name' => $displayName,
                                'available_qty' => $itemModel->available_qty ?? 0,
                                'total_qty' => $itemModel->total_qty ?? 0,
                            ] : null,
                        ];
                    })->values(),
                ];
            });

        return response()->json($requests);
    }

    public function updateStatus(Request $request, BorrowRequest $borrowRequest, PhilSmsService $philSms)
    {
        $request->validate([
            'status' => 'required|in:pending,validated,approved,rejected,returned,return_pending,qr_verified'
        ]);

        $old = $borrowRequest->status;
        $requestedStatusRaw = is_string($request->status) ? strtolower($request->status) : '';
        $new = $requestedStatusRaw === 'qr_verified' ? 'approved' : $requestedStatusRaw;

        // Online requests must pass through the validated stage before approval
        if ($old === 'pending' && $new === 'approved' && $requestedStatusRaw !== 'qr_verified') {
            $new = 'validated';
        }

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
            $borrowRequest->load('items.item', 'items.manpowerRole');

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
                    $placeholderItemId = Item::systemPlaceholderId();
                    foreach ($assignments as $assignment) {
                        $briId = isset($assignment['borrow_request_item_id']) ? (int) $assignment['borrow_request_item_id'] : null;
                        $providedQty = isset($assignment['quantity']) ? (int) $assignment['quantity'] : 0;
                        $providedReason = isset($assignment['quantity_reason']) && $assignment['quantity_reason'] !== ''
                            ? substr($assignment['quantity_reason'], 0, 255)
                            : null;

                        if ($briId) {
                            $bri = BorrowRequestItem::where('id', $briId)
                                ->where('borrow_request_id', $borrowRequest->id)
                                ->lockForUpdate()
                                ->first();

                            if (! $bri) {
                                DB::rollBack();
                                return response()->json(['message' => 'Invalid manpower assignment row.'], 422);
                            }

                            $origQty = (int) $bri->quantity;
                            $newQty = $providedQty;

                            if ($newQty < 0) {
                                DB::rollBack();
                                return response()->json(['message' => 'Invalid quantity provided.'], 422);
                            }

                            if ($newQty > $origQty) {
                                $newQty = $origQty;
                            }

                            $bri->quantity = $newQty;
                            $bri->assigned_manpower = null;
                            $bri->manpower_notes = $providedReason;
                            $bri->assigned_by = \Illuminate\Support\Facades\Auth::id();
                            $bri->assigned_at = now();
                            $bri->save();
                            continue;
                        }

                        $roleId = isset($assignment['manpower_role_id']) ? (int) $assignment['manpower_role_id'] : null;
                        if (! $roleId) {
                            DB::rollBack();
                            return response()->json(['message' => 'A manpower role is required for new assignments.'], 422);
                        }

                        if ($providedQty <= 0) {
                            DB::rollBack();
                            return response()->json(['message' => 'Quantity should be greater than zero for new manpower roles.'], 422);
                        }

                        $role = ManpowerRole::query()->lockForUpdate()->find($roleId);
                        if (! $role) {
                            DB::rollBack();
                            return response()->json(['message' => 'Selected manpower role was not found.'], 422);
                        }

                        $existingRoleRow = BorrowRequestItem::where('borrow_request_id', $borrowRequest->id)
                            ->where('is_manpower', true)
                            ->where('manpower_role_id', $role->id)
                            ->lockForUpdate()
                            ->first();

                        if ($existingRoleRow) {
                            $existingRoleRow->quantity = $providedQty;
                            $existingRoleRow->manpower_notes = $providedReason;
                            $existingRoleRow->assigned_by = \Illuminate\Support\Facades\Auth::id();
                            $existingRoleRow->assigned_at = now();
                            $existingRoleRow->save();
                            continue;
                        }

                        if (! $placeholderItemId) {
                            DB::rollBack();
                            return response()->json(['message' => 'System manpower placeholder item is not configured.'], 422);
                        }

                        BorrowRequestItem::create([
                            'borrow_request_id' => $borrowRequest->id,
                            'item_id' => $placeholderItemId,
                            'quantity' => $providedQty,
                            'requested_quantity' => $providedQty,
                            'assigned_manpower' => null,
                            'manpower_role' => $role->name,
                            'manpower_role_id' => $role->id,
                            'manpower_notes' => $providedReason,
                            'assigned_by' => \Illuminate\Support\Facades\Auth::id(),
                            'assigned_at' => now(),
                            'is_manpower' => true,
                        ]);
                    }
                }

                $latestManpowerCount = $borrowRequest->items()
                    ->where('is_manpower', true)
                    ->sum('quantity');
                $borrowRequest->manpower_count = $latestManpowerCount > 0 ? $latestManpowerCount : null;
                $borrowRequest->manpower_adjustment_reason = null;

                if ($request->filled('manpower_total')) {
                    $borrowRequest->manpower_count = max(0, (int) $request->input('manpower_total'));
                    $borrowRequest->manpower_adjustment_reason = $request->input('manpower_reason')
                        ? substr($request->input('manpower_reason'), 0, 255)
                        : null;
                }
            }


            if ($old !== 'approved' && $new === 'approved') {
                foreach ($borrowRequest->items as $reqItem) {
                    if ($reqItem->is_manpower) {
                        continue;
                    }

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
                            'borrowed_qty'      => 1,
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
                    if ($reqItem->is_manpower) {
                        continue;
                    }

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

            if (in_array($new, ['validated', 'approved', 'rejected'], true)) {
                $borrowerForSms = $borrowRequest->fresh(['user']);
                if ($borrowerForSms) {
                    $philSms->notifyBorrowerStatus($borrowerForSms, $new, $notificationReason);
                }
            }

            try {
                $user = $borrowRequest->user;
                if ($user) {
                    $actor = $request->user();
                    $actorName = $this->resolveActorName($actor);
                    $statusLabel = $this->formatBorrowStatusLabel($new);

                    $items = $borrowRequest->items->map(function (BorrowRequestItem $it) {
                        $displayName = $this->resolveBorrowItemDisplayName($it);

                        return [
                            'id' => $it->item->id ?? null,
                            'name' => $displayName,
                            'quantity' => $it->quantity,
                            'assigned_manpower' => $it->assigned_manpower ?? 0,
                            'manpower_role' => $it->manpower_role ?? null,
                            'manpower_notes' => $it->manpower_notes ?? null,
                        ];
                    })->toArray();

                    $payload = [
                        'type' => 'borrow_status_changed',
                        'message' => $actorName
                            ? sprintf('%s set %s to %s.', $actorName, $borrowRequest->formatted_request_id ?? ('Request #' . $borrowRequest->id), $statusLabel ?? $new)
                            : sprintf('%s is now %s.', $borrowRequest->formatted_request_id ?? ('Request #' . $borrowRequest->id), $statusLabel ?? $new),
                        'borrow_request_id' => $borrowRequest->id,
                        'formatted_request_id' => $borrowRequest->formatted_request_id,
                        'old_status' => $old,
                        'new_status' => $new,
                        'old_status_label' => $this->formatBorrowStatusLabel($old),
                        'status_label' => $statusLabel,
                        'items' => $items,
                        'borrow_date' => $borrowRequest->borrow_date,
                        'return_date' => $borrowRequest->return_date,
                        'reason' => $notificationReason ?? $borrowRequest->reject_reason,
                        'reject_category' => $borrowRequest->reject_category,
                        'actor_id' => $actor?->id,
                        'actor_role' => $actor?->role,
                        'actor_name' => $actorName,
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

    public function scan(Request $request, BorrowRequest $borrowRequest, BorrowRequestFormPdf $formPdf, PhilSmsService $philSms)
    {
        $oldStatus = $borrowRequest->status ?? 'pending';
        $wasUpdated = false;
        $scanTimestamp = now();

        if ($borrowRequest->status !== 'approved') {
            $borrowRequest->status = 'approved';
            $borrowRequest->save();

            event(new BorrowRequestStatusUpdated($borrowRequest->fresh(), $oldStatus, $borrowRequest->status));
            $borrowRequest->refresh();
            $wasUpdated = true;
        }

        if ($wasUpdated) {
            $freshForSms = $borrowRequest->fresh(['user']);
            if ($freshForSms) {
                $philSms->notifyBorrowerStatus($freshForSms, 'approved');
            }
        }

        $message = $wasUpdated
            ? 'Borrow request marked as Approved via QR scan.'
            : 'Borrow request was already marked as Approved.';

        $downloadUrl = null;

        try {
            $result = $formPdf->render($borrowRequest);
            $timestamp = $scanTimestamp->format('YmdHis');
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
                'scan_timestamp' => $scanTimestamp->toIso8601String(),
            ]);
        }

        return view('admin.borrow-requests.scan-result', [
            'borrowRequest' => $borrowRequest,
            'updated' => $wasUpdated,
            'message' => $message,
            'downloadUrl' => $downloadUrl,
            'scanTimestamp' => $scanTimestamp,
        ]);
    }

    protected function allocateInstancesForBorrowRequest(BorrowRequest $borrowRequest): ?array
    {
        // Assumes $borrowRequest->load('items.item', 'items.manpowerRole') has been called by caller if needed.
        foreach ($borrowRequest->items as $requestItem) {
            if ($requestItem->is_manpower) {
                continue;
            }

            $item = $requestItem->item;
            if (! $item) {
                return [
                    'message' => 'One of the requested items could not be found. Allocation aborted.',
                ];
            }

            $needed = (int) $requestItem->quantity;
            if ($needed <= 0) {
                continue;
            }

            $instances = $item->instances()
                ->where('status', 'available')
                ->lockForUpdate()
                ->take($needed)
                ->get();

            $availableCount = $instances->count();
            if ($availableCount < $needed) {
                $shortfall = max(0, $needed - $availableCount);
                return [
                    'message' => $availableCount > 0
                        ? "Only {$availableCount} of {$item->name} available right now (needed {$needed})."
                        : "No available instances for {$item->name}.",
                    'available_instances' => $availableCount,
                    'requested_quantity' => $needed,
                    'shortfall' => $shortfall,
                ];
            }

            foreach ($instances as $inst) {
                $inst->status = 'allocated';
                $inst->save();

                BorrowItemInstance::create([
                    'borrow_request_id' => $borrowRequest->id,
                    'item_id'           => $item->id,
                    'item_instance_id'  => $inst->id,
                    'borrowed_qty'      => 1,
                    'checked_out_at'    => now(),
                    'expected_return_at'=> $borrowRequest->return_date,
                    'return_condition'  => 'pending',
                ]);
            }
            // Stock deduction happens on delivery, not allocation
        }

        return null;
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

    public function printStickers(
        Request $request,
        BorrowRequest $borrowRequest,
        PropertyNumberService $numbers,
        StickerPdfService $stickerPdf
    ) {
        $borrowRequest->loadMissing('user');

        $status = strtolower((string) $borrowRequest->status);
        if ($status === 'qr_verified') {
            $status = 'approved';
        }

        $delivery = strtolower((string) $borrowRequest->delivery_status);

        $eligibleStatuses = ['approved', 'return_pending', 'returned'];
        $eligibleDelivery = ['dispatched', 'delivered'];

        if (! in_array($status, $eligibleStatuses, true) && ! in_array($delivery, $eligibleDelivery, true)) {
            $message = 'Stickers can only be generated for approved borrow requests.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }

        $allocatedInstances = BorrowItemInstance::query()
            ->with(['item', 'instance'])
            ->where('borrow_request_id', $borrowRequest->id)
            ->whereHas('instance')
            ->orderBy('item_id')
            ->orderBy('id')
            ->get()
            ->filter(function (BorrowItemInstance $record) {
                $item = $record->item ?? $record->instance?->item;
                return $record->instance !== null && $item !== null;
            })
            ->values();

        $borrowRequest->loadMissing('items.item');

        $allocatedCountByItem = [];
        $stickerSources = [];

        foreach ($allocatedInstances as $record) {
            $itemModel = $record->item ?? $record->instance?->item;
            if (! $itemModel || ! $record->instance) {
                continue;
            }
            $itemId = (int) $itemModel->id;
            $allocatedCountByItem[$itemId] = ($allocatedCountByItem[$itemId] ?? 0) + 1;
            $stickerSources[] = [
                'instance' => $record->instance,
                'item' => $itemModel,
                'allocated' => true,
            ];
        }

        $allowSuggested = $status === 'approved' && ! in_array($delivery, ['dispatched', 'delivered'], true);

        if ($allowSuggested) {
            foreach ($borrowRequest->items as $requestItem) {
                if ($requestItem->is_manpower) {
                    continue;
                }

                $itemModel = $requestItem->item;
                if (! $itemModel) {
                    continue;
                }

                $needed = (int) $requestItem->quantity;
                $alreadyAllocated = $allocatedCountByItem[$itemModel->id] ?? 0;
                $remaining = max(0, $needed - $alreadyAllocated);

                if ($remaining <= 0) {
                    continue;
                }

                $candidates = ItemInstance::query()
                    ->where('item_id', $itemModel->id)
                    ->where('status', 'available')
                    ->orderBy('property_number')
                    ->limit($remaining)
                    ->get();

                foreach ($candidates as $candidate) {
                    $stickerSources[] = [
                        'instance' => $candidate,
                        'item' => $itemModel,
                        'allocated' => false,
                    ];
                }
            }
        }

        $totalCandidates = count($stickerSources);

        if ($totalCandidates === 0) {
            $message = 'No item instances are currently available for sticker printing.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }

        $requestedQuantity = (int) $request->input('quantity', $totalCandidates);
        if ($requestedQuantity <= 0) {
            $requestedQuantity = $totalCandidates;
        }

        $selectedSources = collect($stickerSources)->take($requestedQuantity);

        $user = $borrowRequest->user;
        $defaultPerson = '';
        if ($user) {
            $defaultPerson = trim((string) ($user->full_name ?? ''));
            if ($defaultPerson === '') {
                $defaultPerson = trim(
                    collect([$user->first_name, $user->middle_name, $user->last_name])
                        ->filter(fn ($part) => $part !== null && $part !== '')
                        ->implode(' ')
                );
            }
        }

        $personAccountable = trim((string) $request->input('person_accountable', ''));
        if ($personAccountable === '' && $defaultPerson !== '') {
            $personAccountable = $defaultPerson;
        }

        $signatureData = (string) $request->input('signature_data', '');
        if (trim($signatureData) === '') {
            $signatureData = '';
        }

        $printDate = Carbon::now()->format('m/d/Y');

        $stickers = $selectedSources->map(function (array $entry) use ($numbers, $personAccountable, $signatureData, $printDate) {
            $instance = $entry['instance'] ?? null;
            $item = $entry['item'] ?? null;

            if (! $instance || ! $item) {
                return null;
            }

            $parsed = [];
            $propertyNumber = (string) ($instance->property_number ?? '');

            if ($propertyNumber !== '') {
                try {
                    $parsed = $numbers->parse($propertyNumber);
                } catch (\Throwable $e) {
                    $parsed = [];
                }
            }

            $acquisitionDate = '';
            if (! empty($item->acquisition_date)) {
                try {
                    $acquisitionDate = Carbon::parse($item->acquisition_date)->format('m/d/Y');
                } catch (\Throwable $e) {
                    $acquisitionDate = (string) $item->acquisition_date;
                }
            }

            $description = trim(strip_tags((string) ($item->description ?? '')));

            return [
                'print_yp' => $parsed['year'] ?? '',
                'print_ppe' => $parsed['category'] ?? ($parsed['category_code'] ?? ''),
                'print_gla' => $parsed['gla'] ?? '',
                'print_serial' => $parsed['serial'] ?? '',
                'print_office' => $parsed['office'] ?? '',
                'print_item' => trim((string) ($item->name ?? '')),
                'print_description' => $description,
                'print_mn' => (string) ($instance->model_no ?? ''),
                'print_sn' => (string) ($instance->serial_no ?? ''),
                'print_ad' => $acquisitionDate,
                'print_pa' => $personAccountable,
                'print_signature' => $signatureData,
                'print_date' => $printDate,
            ];
        })->filter()->values();

        if ($stickers->isEmpty()) {
            $message = 'No sticker data available for this borrow request.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }

        $identifier = $borrowRequest->formatted_request_id ?? ('request-' . $borrowRequest->id);
        $sanitizedIdentifier = preg_replace('/[^A-Za-z0-9_-]+/', '-', $identifier) ?: ('request-' . $borrowRequest->id);
        $filename = sprintf('%s-stickers-%s.pdf', strtolower($sanitizedIdentifier), now()->format('YmdHis'));

        try {
            $result = $stickerPdf->render($stickers->all(), $filename);
        } catch (\Throwable $e) {
            Log::error('borrow.print_stickers_failed', [
                'borrow_request_id' => $borrowRequest->id,
                'error' => $e->getMessage(),
            ]);

            $message = 'Failed to generate sticker PDF.';
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $message,
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', $message);
        }

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($result['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $result['filename'] . '"',
        ]);
    }

    public function dispatch(Request $request, BorrowRequest $borrowRequest, PhilSmsService $philSms)
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
            $borrowRequest->load('items.item', 'items.manpowerRole');
            $statusBeforeDispatch = $borrowRequest->status;

            // Only check available quantity if status is not already approved
            // If already approved, items are already allocated, so we can proceed
            if ($borrowRequest->status !== 'approved') {
                foreach ($borrowRequest->items as $requestItem) {
                    if ($requestItem->is_manpower) {
                        continue;
                    }

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
                $allocationError = $this->allocateInstancesForBorrowRequest($borrowRequest);
                if ($allocationError) {
                    DB::rollBack();
                    return response()->json($allocationError, 422);
                }
            }

            // Re-evaluate allocated instances and ensure they are dispatchable
            $borrowRequest->load('borrowedInstances');
            $instanceIds = $borrowRequest->borrowedInstances
                ->pluck('item_instance_id')
                ->filter()
                ->unique()
                ->values();

            if ($instanceIds->isNotEmpty()) {
                $instances = ItemInstance::query()
                    ->whereIn('id', $instanceIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($borrowRequest->borrowedInstances as $allocation) {
                    $instanceId = $allocation->item_instance_id;
                    if (! $instanceId) {
                        continue;
                    }

                    $instance = $instances->get($instanceId);
                    if (! $instance) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Cannot dispatch: One or more selected item instances could not be found.',
                        ], 422);
                    }

                    $status = strtolower((string) $instance->status);
                    if (! in_array($status, ['available', 'allocated'], true)) {
                        DB::rollBack();
                        $identifier = $instance->property_number
                            ?: ($instance->serial_no ?? $instance->serial ?? ('Instance #' . $instance->id));

                        return response()->json([
                            'message' => 'Cannot dispatch: Item is currently borrowed by another user. Please mark it as returned/collected first.',
                            'item_instance_id' => $instance->id,
                            'property_number' => $instance->property_number,
                            'current_status' => $status,
                            'identifier' => $identifier,
                        ], 422);
                    }
                }
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

            $becameApproved = $statusBeforeDispatch !== 'approved' && $borrowRequest->status === 'approved';

            // notify user - dispatched only (two-step flow)
            $user = $borrowRequest->user;
            if ($user) {
                $actor = $request->user();
                $actorName = $this->resolveActorName($actor);
                $payload = [
                    'type' => 'borrow_dispatched',
                    'message' => $actorName
                        ? sprintf('%s dispatched %s.', $actorName, $borrowRequest->formatted_request_id ?? ('Request #' . $borrowRequest->id))
                        : sprintf('%s has been dispatched.', $borrowRequest->formatted_request_id ?? ('Request #' . $borrowRequest->id)),
                    'borrow_request_id' => $borrowRequest->id,
                    'formatted_request_id' => $borrowRequest->formatted_request_id,
                    'user_id' => $user->id,
                    'user_name' => trim($user->first_name . ' ' . ($user->last_name ?? '')),
                    'actor_id' => $actor?->id,
                    'actor_role' => $actor?->role,
                    'actor_name' => $actorName,
                    'borrow_date' => (string) $borrowRequest->borrow_date,
                    'return_date' => (string) $borrowRequest->return_date,
                    'dispatched_at' => $borrowRequest->dispatched_at ? $borrowRequest->dispatched_at->toDateTimeString() : null,
                ];
                $user->notify(new RequestNotification($payload));
            }

            DB::commit();
            if ($becameApproved) {
                $freshForSms = $borrowRequest->fresh(['user']);
                if ($freshForSms) {
                    $philSms->notifyBorrowerStatus($freshForSms, 'approved');
                }
            }
            return response()->json(['message' => 'Items dispatched successfully.']);
        } catch (\Throwable $e) {
            Log::error('borrow.dispatch_failed', [
                'borrow_request_id' => $borrowRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Failed to dispatch items. Please try again.', 'error' => $e->getMessage()], 500);
        }
    }

    private function makeLetterUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Storage::disk('public')->exists($path)) {
            $disk = Storage::disk('public');
            $diskUrl = $disk->url($path);
            $httpRequest = null;

            try {
                $httpRequest = request();
            } catch (\Throwable $e) {
                $httpRequest = null;
            }

            if ($diskUrl && filter_var($diskUrl, FILTER_VALIDATE_URL)) {
                if ($httpRequest) {
                    $parsed = parse_url($diskUrl) ?: [];
                    $port = $httpRequest->getPort();

                    $isDefaultPort = in_array($port, [null, 80, 443], true);
                    $missingPort = empty($parsed['port']);

                    if ($missingPort && ! $isDefaultPort) {
                        $scheme = $httpRequest->getScheme();
                        $host = $httpRequest->getHost();
                        $pathPart = $parsed['path'] ?? '';
                        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

                        return sprintf('%s://%s:%d%s%s', $scheme, $host, $port, $pathPart, $query);
                    }
                }

                return $diskUrl;
            }

            $relative = $diskUrl ?: ('/storage/' . ltrim($path, '/'));
            if ($relative && $relative[0] !== '/') {
                $relative = '/' . ltrim($relative, '/');
            }

            if ($httpRequest) {
                return rtrim($httpRequest->getSchemeAndHttpHost(), '/') . $relative;
            }

            return $relative;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return null;
    }


    private function resolveActorName(?\App\Models\User $actor): ?string
    {
        if (! $actor) {
            return null;
        }

        $preferred = trim((string) ($actor->full_name ?? ''));
        if ($preferred !== '') {
            return $preferred;
        }

        $fallback = trim((string) (($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')));
        if ($fallback !== '') {
            return $fallback;
        }

        return $actor->email ?? null;
    }

    private function formatBorrowStatusLabel(?string $status): ?string
    {
        if (! $status) {
            return null;
        }

        return match ($status) {
            'pending' => 'Pending Review',
            'validated' => 'Validated',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'returned' => 'Returned',
            'return_pending' => 'Awaiting Return',
            'qr_verified' => 'QR Verified',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }
}
