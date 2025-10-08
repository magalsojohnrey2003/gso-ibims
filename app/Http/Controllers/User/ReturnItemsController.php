<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BorrowRequest;
use App\Models\ReturnRequest;
use App\Models\ReturnItem;
use Illuminate\Http\Request;
use App\Models\ItemInstance;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Notifications\RequestNotification;

class ReturnItemsController extends Controller
{
    public function index()
    {
        return view('user.return-items.index');
    }

    public function searchByProperty(Request $request)
    {
        $term = trim($request->query('q', ''));
        if ($term === '') {
            return response()->json([]);
        }

        $instances = ItemInstance::with([
            'item',
            'borrowRecords' => function ($query) {
                $query->whereNull('returned_at')
                    ->with(['borrowRequest.user'])
                    ->orderByDesc('checked_out_at');
            },
        ])
            ->searchProperty($term)
            ->limit(10)
            ->get();

        $results = $instances->map(function (ItemInstance $instance) {
            $active = $instance->borrowRecords->first();
            $borrowRequest = $active?->borrowRequest;
            $borrower = $borrowRequest?->user;

            return [
                'id' => $instance->id,
                'property_number' => $instance->property_number,
                'serial' => $instance->serial,
                'status' => $instance->status,
                'item_id' => $instance->item_id,
                'item_name' => $instance->item?->name,
                'office_code' => $instance->office_code,
                'borrow_status' => $active ? 'borrowed' : 'available',
                'borrow_request_id' => $active?->borrow_request_id,
                'borrow_date' => $borrowRequest?->borrow_date ? (string) $borrowRequest->borrow_date : null,
                'return_date' => $borrowRequest?->return_date ? (string) $borrowRequest->return_date : null,
                'borrowed_at' => $active?->checked_out_at ? (string) $active->checked_out_at : null,
                'expected_return_at' => $active?->expected_return_at ? (string) $active->expected_return_at : null,
                'borrower' => $borrower ? [
                'id' => $borrower->id,
                'name' => trim(($borrower->first_name ?? '') . ' ' . ($borrower->last_name ?? '')),
                ] : null,
            ];
        });

        return response()->json($results);
    }

    private function normalizeCondition(?string $cond): string
    {
        if (! $cond) return 'good';
        $c = strtolower(trim($cond));
        $map = [
            'good' => 'good',
            'fair' => 'minor_damage',
            'damaged' => 'major_damage',
            'minor_damage' => 'minor_damage',
            'major_damage' => 'major_damage',
            'missing' => 'missing',
            'needs_cleaning' => 'needs_cleaning',
        ];
        return $map[$c] ?? 'good';
    }

    public function list(Request $request)
    {
        $requests = BorrowRequest::with(['items.item'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'approved')
            ->whereDoesntHave('returnRequests', function ($q) {
                $q->where('status', 'pending');
            })
            ->latest()
            ->get();

        return response()->json($requests);
    }

    public function requestReturn(Request $request)
    {
        $rules = [
            'borrow_requests' => 'required|array|min:1',
            'borrow_requests.*.borrow_request_id' => 'sometimes|required|exists:borrow_requests,id',
            'borrow_requests.*.items' => 'sometimes|array|min:1',
            'borrow_requests.*.items.*.quantity' => 'sometimes|integer|min:1',
            'borrow_requests.*.items.*.item_id' => 'sometimes|required_with:borrow_requests.*.items|exists:items,id',
            'borrow_requests.*.items.*.item_instance_id' => 'nullable|exists:item_instances,id',
            'borrow_requests.*.items.*.condition' => 'sometimes|string',
            'borrow_requests.*.items.*.remarks' => 'nullable|string|max:500',
            'condition' => 'sometimes|required_without:borrow_requests.*.items|in:good,fair,damaged',
            'damageReason' => 'nullable|required_if:condition,damaged|string|max:500',
        ];

        $validated = $request->validate($rules);

        $inputBorrowRequests = $request->input('borrow_requests', []);
        $createdRequests = [];
        $skippedIds = [];

        DB::beginTransaction();
        try {
            foreach ($inputBorrowRequests as $brIndex => $brEntry) {
                if (is_array($brEntry) && isset($brEntry['borrow_request_id'])) {
                    $borrowRequestId = $brEntry['borrow_request_id'];
                    $itemsPayload = $brEntry['items'] ?? null;
                } else {
                    $borrowRequestId = $brEntry;
                    $itemsPayload = null;
                }

                $borrowRequest = BorrowRequest::where('id', $borrowRequestId)
                    ->where('user_id', $request->user()->id)
                    ->where('status', 'approved')
                    ->first();

                if (! $borrowRequest) {
                    $skippedIds[] = $borrowRequestId;
                    continue;
                }

                $existsPending = ReturnRequest::where('borrow_request_id', $borrowRequest->id)
                    ->where('status', 'pending')
                    ->exists();

                if ($existsPending) {
                    $skippedIds[] = $borrowRequest->id;
                    continue;
                }

                $rr = ReturnRequest::create([
                    'borrow_request_id' => $borrowRequest->id,
                    'user_id' => $request->user()->id,
                    'condition' => 'fair',  
                    'damage_reason' => null,
                    'status' => 'pending',
                ]);

                if ($borrowRequest->status === 'approved') {
                    $borrowRequest->status = 'return_pending';
                    $borrowRequest->save();
                }

                $createdRequests[] = [
                    'return_request_id' => $rr->id,
                    'borrow_request_id' => $borrowRequest->id,
                ];

                $createdQtyPerItem = []; 
                $conditionCounts = [];  

                if (is_array($itemsPayload) && count($itemsPayload)) {
                    foreach ($itemsPayload as $itemIndex => $itemRow) {
                        $itemId = $itemRow['item_id'] ?? $itemRow['itemId'] ?? null;
                        $instanceId = $itemRow['item_instance_id'] ?? $itemRow['itemInstanceId'] ?? null;
                        $rawCond = $itemRow['condition'] ?? 'good';
                        $cond = $this->normalizeCondition($rawCond);
                        $remarks = $itemRow['remarks'] ?? null;
                        $qty = isset($itemRow['quantity']) ? (int) $itemRow['quantity'] : null;

                        $borrowRow = $borrowRequest->items->firstWhere('item_id', $itemId);
                        $borrowedQty = $borrowRow ? (int) $borrowRow->quantity : 1;

                        $fileKey = "borrow_requests.$brIndex.items.$itemIndex.photo";
                        $photoPath = null;
                        if ($request->hasFile($fileKey) && $request->file($fileKey)->isValid()) {
                            $photoPath = $request->file($fileKey)->store('returns', 'public');
                        }

                        if ($instanceId) {
                            $already = $createdQtyPerItem[$itemId] ?? 0;
                            if (($already + 1) > $borrowedQty) {
                                DB::rollBack();
                                return response()->json(['message' => "Return quantities for item {$itemId} exceed borrowed quantity."], 422);
                            }
                            ReturnItem::create([
                                'return_request_id' => $rr->id,
                                'borrow_request_id' => $borrowRequest->id,
                                'item_id' => $itemId,
                                'item_instance_id' => $instanceId,
                                'condition' => $cond,
                                'remarks' => $remarks,
                                'photo' => $photoPath,
                                'quantity' => 1,
                            ]);
                            $createdQtyPerItem[$itemId] = ($createdQtyPerItem[$itemId] ?? 0) + 1;
                            $conditionCounts[$cond] = ($conditionCounts[$cond] ?? 0) + 1;
                        } elseif ($qty !== null) {
                            $already = $createdQtyPerItem[$itemId] ?? 0;
                            if (($already + $qty) > $borrowedQty) {
                                DB::rollBack();
                                return response()->json(['message' => "Return quantities for item {$itemId} exceed borrowed quantity."], 422);
                            }
                            ReturnItem::create([
                                'return_request_id' => $rr->id,
                                'borrow_request_id' => $borrowRequest->id,
                                'item_id' => $itemId,
                                'item_instance_id' => null,
                                'condition' => $cond,
                                'remarks' => $remarks,
                                'photo' => $photoPath,
                                'quantity' => $qty,
                            ]);
                            $createdQtyPerItem[$itemId] = ($createdQtyPerItem[$itemId] ?? 0) + $qty;
                            $conditionCounts[$cond] = ($conditionCounts[$cond] ?? 0) + $qty;
                        } else {
                            $useQty = $borrowedQty;
                            $already = $createdQtyPerItem[$itemId] ?? 0;
                            if (($already + $useQty) > $borrowedQty) {
                                DB::rollBack();
                                return response()->json(['message' => "Return quantities for item {$itemId} exceed borrowed quantity."], 422);
                            }
                            ReturnItem::create([
                                'return_request_id' => $rr->id,
                                'borrow_request_id' => $borrowRequest->id,
                                'item_id' => $itemId,
                                'item_instance_id' => null,
                                'condition' => $cond,
                                'remarks' => $remarks,
                                'photo' => $photoPath,
                                'quantity' => $useQty,
                            ]);
                            $createdQtyPerItem[$itemId] = ($createdQtyPerItem[$itemId] ?? 0) + $useQty;
                            $conditionCounts[$cond] = ($conditionCounts[$cond] ?? 0) + $useQty;
                        }
                    } 
                } else {
                    $legacyCond = $request->input('condition', 'good');
                    $legacyCondNormalized = $this->normalizeCondition($legacyCond);
                    $legacyReason = $request->input('damageReason', null);

                    foreach ($borrowRequest->items as $borrowItem) {
                        ReturnItem::create([
                            'return_request_id' => $rr->id,
                            'borrow_request_id' => $borrowRequest->id,
                            'item_id' => $borrowItem->item_id,
                            'item_instance_id' => null,
                            'condition' => $legacyCondNormalized,
                            'remarks' => $legacyCondNormalized === 'major_damage' ? $legacyReason : null,
                            'photo' => null,
                            'quantity' => (int) $borrowItem->quantity,
                        ]);

                        $createdQtyPerItem[$borrowItem->item_id] = ($createdQtyPerItem[$borrowItem->item_id] ?? 0) + (int) $borrowItem->quantity;
                        $conditionCounts[$legacyCondNormalized] = ($conditionCounts[$legacyCondNormalized] ?? 0) + (int) $borrowItem->quantity;
                    }

                    if ($legacyCondNormalized === 'major_damage' && $legacyReason) {
                        $rr->damage_reason = $legacyReason;
                    }
                } 

                foreach ($borrowRequest->items as $borrowItem) {
                    $itemId = $borrowItem->item_id;
                    $totalQty = (int) $borrowItem->quantity;
                    $created = $createdQtyPerItem[$itemId] ?? 0;
                    $remaining = $totalQty - $created;
                    if ($remaining < 0) {
                        DB::rollBack();
                        return response()->json(['message' => "Return item quantities exceed borrowed quantity for item {$itemId}."], 422);
                    }
                    if ($remaining > 0) {
                        ReturnItem::create([
                            'return_request_id' => $rr->id,
                            'borrow_request_id' => $borrowRequest->id,
                            'item_id' => $itemId,
                            'item_instance_id' => null,
                            'condition' => 'good',
                            'remarks' => null,
                            'photo' => null,
                            'quantity' => $remaining,
                        ]);
                        $conditionCounts['good'] = ($conditionCounts['good'] ?? 0) + $remaining;
                    }
                }

                $summaryCondition = 'fair';
                if (!empty($conditionCounts)) {
                    if (count($conditionCounts) === 1 && isset($conditionCounts['good'])) {
                        $summaryCondition = 'good';
                    } else {
                        $severeKeys = ['major_damage', 'missing'];
                        $severeFound = false;
                        foreach ($severeKeys as $k) {
                            if (!empty($conditionCounts[$k])) { $severeFound = true; break; }
                        }
                        $summaryCondition = $severeFound ? 'damaged' : 'fair';
                    }
                }
                $rr->condition = $summaryCondition;
                $rr->save();


            } 

            DB::commit();

            $admins = User::where('role', 'admin')->get();
            if ($admins->count()) {
                foreach ($createdRequests as $entry) {
                    $payload = [
                        'type' => 'return_submitted',
                        'message' => "Return request #{$entry['return_request_id']} is ready for pickup.",
                        'return_request_id' => $entry['return_request_id'],
                        'borrow_request_id' => $entry['borrow_request_id'],
                        'user_id' => $request->user()->id,
                        'user_name' => trim($request->user()->first_name . ' ' . ($request->user()->last_name ?? '')),
                    ];
                    Notification::send($admins, new RequestNotification($payload));
                }
            }

            $messages = [];
            if (count($createdRequests)) {
                $messages[] = count($createdRequests) . ' return request(s) submitted successfully';
            }
            if (count($skippedIds)) {
                $messages[] = count($skippedIds) . ' request(s) were skipped because a pending return already exists or borrow record invalid';
            }

            return response()->json([
                'message' => $messages ? implode('. ', $messages) : 'No changes made.',
                'created_ids' => array_map(fn($entry) => $entry['return_request_id'], $createdRequests),
                'skipped_ids' => $skippedIds,
                'count' => count($createdRequests),
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to submit return requests.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update existing return request while it's still pending.
     */
    public function update(Request $request, ReturnRequest $returnRequest)
    {
        if ($returnRequest->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($returnRequest->status !== 'pending') {
            return response()->json(['message' => 'Only pending return requests may be edited'], 422);
        }

        $rules = [
            'items' => 'sometimes|array|min:1',
            'items.*.quantity' => 'sometimes|integer|min:1',
            'items.*.item_id' => 'sometimes|required_with:items|exists:items,id',
            'items.*.item_instance_id' => 'nullable|exists:item_instances,id',
            'items.*.condition' => 'sometimes|string',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
        $validated = $request->validate($rules);

        $borrowRequest = $returnRequest->borrowRequest;
        if (! $borrowRequest) {
            return response()->json(['message' => 'Associated borrow request not found'], 422);
        }

        DB::beginTransaction();
        $uploadedNewPhotos = [];
        try {
            $oldItems = ReturnItem::where('return_request_id', $returnRequest->id)->get();
            $oldPhotos = $oldItems->pluck('photo')->filter()->unique()->values()->all();

            ReturnItem::where('return_request_id', $returnRequest->id)->delete();

            $itemsPayload = $request->input('items', null);
            $createdQtyPerItem = [];
            $conditionCounts = [];

            if (is_array($itemsPayload) && count($itemsPayload)) {
                foreach ($itemsPayload as $itemIndex => $itemRow) {
                    $itemId = $itemRow['item_id'] ?? null;
                    $instanceId = $itemRow['item_instance_id'] ?? null;
                    $rawCond = $itemRow['condition'] ?? 'good';
                    $cond = $this->normalizeCondition($rawCond);
                    $remarks = $itemRow['remarks'] ?? null;
                    $qty = isset($itemRow['quantity']) ? (int) $itemRow['quantity'] : null;

                    $fileKey = "items.$itemIndex.photo";
                    $photoPath = null;
                    if ($request->hasFile($fileKey) && $request->file($fileKey)->isValid()) {
                        $photoPath = $request->file($fileKey)->store('returns', 'public');
                        $uploadedNewPhotos[] = $photoPath;
                    }

                    $borrowRow = $borrowRequest->items->firstWhere('item_id', $itemId);
                    $borrowedQty = $borrowRow ? (int) $borrowRow->quantity : 1;

                    if ($instanceId) {
                        $already = $createdQtyPerItem[$itemId] ?? 0;
                        if (($already + 1) > $borrowedQty) {
                            DB::rollBack();
                            return response()->json(['message' => "Return quantities for item {$itemId} exceed borrowed quantity."], 422);
                        }
                        ReturnItem::create([
                            'return_request_id' => $returnRequest->id,
                            'borrow_request_id' => $borrowRequest->id,
                            'item_id' => $itemId,
                            'item_instance_id' => $instanceId,
                            'condition' => $cond,
                            'remarks' => $remarks,
                            'photo' => $photoPath,
                            'quantity' => 1,
                        ]);
                        $createdQtyPerItem[$itemId] = ($createdQtyPerItem[$itemId] ?? 0) + 1;
                        $conditionCounts[$cond] = ($conditionCounts[$cond] ?? 0) + 1;
                    } elseif ($qty !== null) {
                        $already = $createdQtyPerItem[$itemId] ?? 0;
                        if (($already + $qty) > $borrowedQty) {
                            DB::rollBack();
                            return response()->json(['message' => "Return quantities for item {$itemId} exceed borrowed quantity."], 422);
                        }
                        ReturnItem::create([
                            'return_request_id' => $returnRequest->id,
                            'borrow_request_id' => $borrowRequest->id,
                            'item_id' => $itemId,
                            'item_instance_id' => null,
                            'condition' => $cond,
                            'remarks' => $remarks,
                            'photo' => $photoPath,
                            'quantity' => $qty,
                        ]);
                        $createdQtyPerItem[$itemId] = ($createdQtyPerItem[$itemId] ?? 0) + $qty;
                        $conditionCounts[$cond] = ($conditionCounts[$cond] ?? 0) + $qty;
                    } else {
                        $useQty = $borrowedQty;
                        $already = $createdQtyPerItem[$itemId] ?? 0;
                        if (($already + $useQty) > $borrowedQty) {
                            DB::rollBack();
                            return response()->json(['message' => "Return quantities for item {$itemId} exceed borrowed quantity."], 422);
                        }
                        ReturnItem::create([
                            'return_request_id' => $returnRequest->id,
                            'borrow_request_id' => $borrowRequest->id,
                            'item_id' => $itemId,
                            'item_instance_id' => null,
                            'condition' => $cond,
                            'remarks' => $remarks,
                            'photo' => $photoPath,
                            'quantity' => $useQty,
                        ]);
                        $createdQtyPerItem[$itemId] = ($createdQtyPerItem[$itemId] ?? 0) + $useQty;
                        $conditionCounts[$cond] = ($conditionCounts[$cond] ?? 0) + $useQty;
                    }
                } 
            } else {
                foreach ($borrowRequest->items as $borrowItem) {
                    ReturnItem::create([
                        'return_request_id' => $returnRequest->id,
                        'borrow_request_id' => $borrowRequest->id,
                        'item_id' => $borrowItem->item_id,
                        'item_instance_id' => null,
                        'condition' => 'good',
                        'remarks' => null,
                        'photo' => null,
                        'quantity' => (int) $borrowItem->quantity,
                    ]);
                    $createdQtyPerItem[$borrowItem->item_id] = ($createdQtyPerItem[$borrowItem->item_id] ?? 0) + (int) $borrowItem->quantity;
                    $conditionCounts['good'] = ($conditionCounts['good'] ?? 0) + (int) $borrowItem->quantity;
                }
            }

            foreach ($borrowRequest->items as $borrowItem) {
                $itemId = $borrowItem->item_id;
                $totalQty = (int) $borrowItem->quantity;
                $created = $createdQtyPerItem[$itemId] ?? 0;
                $remaining = $totalQty - $created;
                if ($remaining < 0) {
                    DB::rollBack();
                    return response()->json(['message' => "Return item quantities exceed borrowed quantity for item {$itemId}."], 422);
                }
                if ($remaining > 0) {
                    ReturnItem::create([
                        'return_request_id' => $returnRequest->id,
                        'borrow_request_id' => $borrowRequest->id,
                        'item_id' => $itemId,
                        'item_instance_id' => null,
                        'condition' => 'good',
                        'remarks' => null,
                        'photo' => null,
                        'quantity' => $remaining,
                    ]);
                    $conditionCounts['good'] = ($conditionCounts['good'] ?? 0) + $remaining;
                }
            }

            $summaryCondition = 'fair';
            if (!empty($conditionCounts)) {
                if (count($conditionCounts) === 1 && isset($conditionCounts['good'])) {
                    $summaryCondition = 'good';
                } else {
                    $severeKeys = ['major_damage', 'missing'];
                    $severeFound = false;
                    foreach ($severeKeys as $k) {
                        if (!empty($conditionCounts[$k])) { $severeFound = true; break; }
                    }
                    $summaryCondition = $severeFound ? 'damaged' : 'fair';
                }
            }

            $returnRequest->condition = $summaryCondition;
            $returnRequest->save();

            DB::commit();

            try {
                if (!empty($oldPhotos)) {
                    foreach ($oldPhotos as $path) {
                        if ($path) {
                            Storage::disk('public')->delete($path);
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('Failed to delete old return item photos: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Return request updated',
                'return_request_id' => $returnRequest->id,
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            if (!empty($uploadedNewPhotos)) {
                try {
                    foreach ($uploadedNewPhotos as $p) {
                        if ($p) Storage::disk('public')->delete($p);
                    }
                } catch (\Throwable $_) {}
            }
            return response()->json([
                'message' => 'Failed to update return request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
