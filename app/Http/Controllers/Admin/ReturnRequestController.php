<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Support\Facades\Storage;
use App\Models\ItemDamageReport;


class ReturnRequestController extends Controller
{

    public function index()
    {
        return view('admin.return-requests.index');
    }

    public function list()
    {
        $requests = ReturnRequest::with(['user', 'borrowRequest.items.item'])
            ->latest()
            ->get()
            ->map(function ($req) {
                return [
                    'id'            => $req->id,
                    'status'        => $req->status, 
                    'condition'     => $req->condition ?? 'N/A',
                    'damage_reason' => $req->damage_reason,
                    'user'          => [
                        'first_name' => $req->user->first_name ?? 'Unknown',
                        'last_name'  => $req->user->last_name ?? '',
                    ],
                    'borrow_request' => $req->borrowRequest ? [
                        'id'          => $req->borrowRequest->id,
                        'borrow_date' => $req->borrowRequest->borrow_date,
                        'return_date' => $req->borrowRequest->return_date,
                        'items'       => $req->borrowRequest->items->map(function ($item) {
                            return [
                                'quantity' => $item->quantity,
                                'item' => [
                                    'name' => $item->item->name ?? 'Unknown Item',
                                ],
                            ];
                        })->values()->all(),
                    ] : null,
                ];
            });

        return response()->json($requests);
    }

    /**
     * Process a return request (approve / reject).
     *
     * Approve -> return items to inventory, borrow → returned
     * Reject  -> reset borrow → approved (so user can re-submit)
     */
    public function process(Request $request, ReturnRequest $returnRequest)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $returnRequest->load([
            'borrowRequest.items.item',
            'borrowRequest.borrowedInstances.instance',
        ]);

        DB::beginTransaction();
        try {
            $borrowRequest = $returnRequest->borrowRequest;
            $returnRequest->status = $request->status;
            $returnRequest->processed_by = $request->user()->id;
            $returnRequest->save();

            $borrowRequest = $returnRequest->borrowRequest;
            $user = $returnRequest->user;
            if ($user) {
                $payload = [
                    'type' => 'return_processed',
                    'message' => "Your return request #{$returnRequest->id} has been {$returnRequest->status}.",
                    'return_request_id' => $returnRequest->id,
                    'borrow_request_id' => $borrowRequest->id ?? null,
                    'status' => $returnRequest->status,
                    'condition' => $returnRequest->condition,
                    'damage_reason' => $returnRequest->damage_reason,
                ];
                $user->notify(new \App\Notifications\RequestNotification($payload));
            }


            if ($borrowRequest) {
                if ($request->status === 'approved') {
                    $now = now();
                    $borrowRequest->loadMissing('borrowedInstances.instance');

                    foreach ($borrowRequest->borrowedInstances as $borrowedInstance) {
                        if ($borrowedInstance->returned_at) {
                            continue;
                        }

                        $borrowedInstance->returned_at = $now;
                        $borrowedInstance->save();

                        if ($borrowedInstance->instance) {
                            $borrowedInstance->instance->status = 'available';
                            $borrowedInstance->instance->save();
                        }
                    }

                    foreach ($borrowRequest->items as $borrowItem) {
                        $item = $borrowItem->item;
                        if (! $item) continue;

                        $current = (int) ($item->available_qty ?? 0);
                        $toAdd   = (int) $borrowItem->quantity;
                        $new     = $current + $toAdd;

                        $item->available_qty = isset($item->total_qty)
                            ? min($new, (int) $item->total_qty)
                            : $new;

                        $item->save();
                    }

                    $borrowRequest->status = 'returned';
                    $borrowRequest->save();
                } else {
                    $borrowRequest->status = 'approved';
                    $borrowRequest->save();
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Return request {$request->status} successfully.",
                'status'  => $returnRequest->status,
                'return_request_id' => $returnRequest->id,
                'borrow_request_id' => $borrowRequest->id ?? null,
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => '❌ Failed processing return request.',
                'error'   => $e->getMessage(),
            ], 500);
        }
        
    }
    
    public function show(ReturnRequest $returnRequest)
    {
        $returnRequest->load([
            'user',
            'borrowRequest.items.item',
            'returnItems.item',
            'returnItems.instance',
        ]);

        $borrow = $returnRequest->borrowRequest;
        $borrowItemsMap = collect([]);
        if ($borrow) {
            $borrowItemsMap = $borrow->items->keyBy('item_id');
        }

        $returnItems = $returnRequest->returnItems->map(function ($ri) use ($borrowItemsMap) {
            $quantity = 1;
            if (empty($ri->item_instance_id)) {
                $borrowRow = $borrowItemsMap->get($ri->item_id);
                if ($borrowRow) {
                    $quantity = (int) ($borrowRow->quantity ?? 1);
                } else {
                    $quantity = 1;
                }
            } else {
                $quantity = 1;
            }

            return [
                'id' => $ri->id,
                'item_id' => $ri->item_id,
                'item_name' => $ri->item->name ?? null,
                'item_instance_id' => $ri->item_instance_id,
                'serial' => $ri->instance->serial ?? null,
                'condition' => $ri->condition,
                'remarks' => $ri->remarks,
                'photo' => $ri->photo ?? null,
                'photo_url' => $ri->photo ? Storage::disk('public')->url($ri->photo) : null,
                'quantity' => $quantity,
                'created_at' => $ri->created_at?->toDateTimeString(),
            ];
        })->values();

        $counts = [];
        foreach ($returnItems as $ri) {
            $cond = $ri['condition'] ?? 'good';
            $counts[$cond] = ($counts[$cond] ?? 0) + ($ri['quantity'] ?? 1);
        }

        $borrowItems = $borrow ? $borrow->items->map(function ($it) {
            return [
                'quantity' => $it->quantity,
                'item' => [
                    'name' => $it->item->name ?? 'Unknown',
                ],
            ];
        })->values()->all() : [];

        $instanceIds = $returnItems->pluck('item_instance_id')->filter()->unique()->all();

        $damageReports = collect();
        if ($returnRequest->borrow_request_id || ! empty($instanceIds)) {
            $damageReportsQuery = ItemDamageReport::with(['itemInstance.item', 'reporter']);

            if ($returnRequest->borrow_request_id) {
                $damageReportsQuery->where('borrow_request_id', $returnRequest->borrow_request_id);
                if (! empty($instanceIds)) {
                    $damageReportsQuery->orWhereIn('item_instance_id', $instanceIds);
                }
            } elseif (! empty($instanceIds)) {
                $damageReportsQuery->whereIn('item_instance_id', $instanceIds);
            }

            $damageReports = $damageReportsQuery->get()->map(function (ItemDamageReport $report) {
                return [
                    'id' => $report->id,
                    'item_instance_id' => $report->item_instance_id,
                    'borrow_request_id' => $report->borrow_request_id,
                    'description' => $report->description,
                    'photos' => $report->photos ?? [],
                    'status' => $report->status,
                    'reported_by' => $report->reported_by,
                    'reporter_name' => $report->reporter ? trim(($report->reporter->first_name ?? '') . ' ' . ($report->reporter->last_name ?? '')) : null,
                    'created_at' => $report->created_at?->toDateTimeString(),
                ];
            });
        }
        return response()->json([
            'id' => $returnRequest->id,
            'borrow_date' => $borrow?->borrow_date ?? null,
            'return_date' => $borrow?->return_date ?? null,
            'status' => $returnRequest->status,
            'user' => $returnRequest->user?->first_name ?? 'Unknown',
            'items' => $borrowItems,
            'condition' => $returnRequest->condition ?? 'N/A',
            'damage_reason' => $returnRequest->damage_reason ?? null,
            'return_items' => $returnItems,
            'damage_reports' => $damageReports,
            'counts' => $counts,
        ]);
    }
}
