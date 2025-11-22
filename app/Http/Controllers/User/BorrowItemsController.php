<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Events\BorrowRequestSubmitted;
use App\Notifications\RequestNotification;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Services\BorrowRequestFormPdf; 
use Illuminate\Support\Facades\Storage;

class BorrowItemsController extends Controller
{
    private string $defaultPhoto = 'images/item.png';
    private array $nonBorrowableInstanceStatuses = ['damaged', 'missing', 'under_repair'];

    private function getSafeBorrowLimit(Item $item): int
    {
        if (isset($item->safe_borrow_qty) && is_numeric($item->safe_borrow_qty)) {
            return max(0, (int) $item->safe_borrow_qty);
        }

        if (isset($item->non_borrowable_instances_count) && is_numeric($item->non_borrowable_instances_count)) {
            $nonBorrowableCount = (int) $item->non_borrowable_instances_count;
        } elseif (isset($item->damaged_missing_qty) && is_numeric($item->damaged_missing_qty)) {
            // Backward compatibility if older property is still set on the model instance
            $nonBorrowableCount = (int) $item->damaged_missing_qty;
        } else {
            $nonBorrowableCount = (int) $item->instances()
                ->whereIn('status', $this->nonBorrowableInstanceStatuses)
                ->count();
        }

        return max(0, (int) $item->total_qty - $nonBorrowableCount);
    }

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $items = Item::query()
            ->withCount([
                'instances as non_borrowable_instances_count' => function ($query) {
                    $query->whereIn('status', $this->nonBorrowableInstanceStatuses);
                },
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
            })
            ->get();

        // Add is_new flag for items created today
        $today = now()->startOfDay();
        foreach ($items as $item) {
            $item->is_new = $item->created_at && $item->created_at->gte($today);
            $item->non_borrowable_instances_count = (int) ($item->non_borrowable_instances_count ?? 0);
            $item->safe_borrow_qty = $this->getSafeBorrowLimit($item);
            // The category_name will be automatically available via the accessor
        }

        $borrowList = Session::get('borrowList', []);

        // Remove deleted items automatically
        foreach ($borrowList as $id => $listItem) {
            if (!Item::where('id', $id)->exists()) {
                unset($borrowList[$id]);
            }
        }

        if (!empty($borrowList)) {
            $itemModels = Item::query()
                ->withCount([
                    'instances as non_borrowable_instances_count' => function ($query) {
                        $query->whereIn('status', $this->nonBorrowableInstanceStatuses);
                    },
                ])
                ->whereIn('id', array_keys($borrowList))
                ->get()
                ->keyBy('id');

            foreach ($borrowList as $id => &$entry) {
                $model = $itemModels->get($id);
                if (!$model) {
                    continue;
                }

                $safeMax = $this->getSafeBorrowLimit($model);
                $entry['total_qty'] = (int) $model->total_qty;
                $entry['safe_max_qty'] = max(0, $safeMax);
                $entry['available_qty'] = (int) $model->available_qty;

                $currentQty = max(1, (int) ($entry['qty'] ?? 1));
                if ($safeMax > 0 && $currentQty > $safeMax) {
                    $entry['qty'] = $safeMax;
                }
            }
            unset($entry);
        }

        Session::put('borrowList', $borrowList);

        return view('user.borrow-items.index', [
            'items'            => $items,
            'borrowList'       => $borrowList,
            'borrowListCount'  => count($borrowList),
            'defaultPhoto'     => $this->defaultPhoto,
        ]);
    }

    /**
     * Dedicated Borrow List page (previously cart page)
     */
    public function borrowList(Request $request)
    {
        $borrowList = Session::get('borrowList', []);

        // Remove deleted items automatically
        foreach ($borrowList as $id => $listItem) {
            if (!Item::where('id', $id)->exists()) {
                unset($borrowList[$id]);
            }
        }

        if (!empty($borrowList)) {
            $borrowItems = Item::query()
                ->withCount([
                    'instances as non_borrowable_instances_count' => function ($query) {
                        $query->whereIn('status', $this->nonBorrowableInstanceStatuses);
                    },
                ])
                ->whereIn('id', array_keys($borrowList))
                ->get()
                ->keyBy('id');

            foreach ($borrowList as $id => &$entry) {
                $itemModel = $borrowItems->get($id);
                if (!$itemModel) {
                    continue;
                }

                $safeMax = $this->getSafeBorrowLimit($itemModel);
                $entry['total_qty'] = (int) $itemModel->total_qty;
                $entry['safe_max_qty'] = max(0, $safeMax);
                $entry['available_qty'] = (int) $itemModel->available_qty;

                $currentQty = max(1, (int) ($entry['qty'] ?? 1));
                if ($safeMax > 0 && $currentQty > $safeMax) {
                    $entry['qty'] = $safeMax;
                }
            }
            unset($entry);
        }
        Session::put('borrowList', $borrowList);

        return view('user.borrow-items.borrowList', [
            'borrowList'      => $borrowList,
            'borrowListCount' => count($borrowList),
            'defaultPhoto'    => $this->defaultPhoto,
        ]);
    }

    // ✅ Add to Borrow List
    public function addToBorrowList(Request $request, Item $item)
    {
        $item->loadCount([
            'instances as non_borrowable_instances_count' => function ($query) {
                $query->whereIn('status', $this->nonBorrowableInstanceStatuses);
            },
        ]);

        $safeBorrowLimit = $this->getSafeBorrowLimit($item);

        if ($safeBorrowLimit <= 0) {
            return back()->withErrors([
                'item' => "All units of {$item->name} are currently marked as damaged, missing, or under maintenance.",
            ]);
        }

        $qty = max(1, (int) $request->input('qty', 1));
        $qty = min($qty, $safeBorrowLimit);

        $borrowList = Session::get('borrowList', []);

        $photoPath = $item->photo ?: $this->defaultPhoto;

        if (isset($borrowList[$item->id])) {
            $currentQty = max(1, (int) ($borrowList[$item->id]['qty'] ?? 1));
            $newQty = min($currentQty + $qty, $safeBorrowLimit);

            if ($newQty === $currentQty) {
                return back()->withErrors([
                    'item' => "You already have the maximum usable quantity ({$safeBorrowLimit}) of {$item->name} in your list.",
                ]);
            }
            $borrowList[$item->id]['qty'] = $newQty;
            $borrowList[$item->id]['safe_max_qty'] = $safeBorrowLimit;
            $borrowList[$item->id]['total_qty'] = (int) $item->total_qty;
            $borrowList[$item->id]['available_qty'] = (int) $item->available_qty;
        } else {
            $borrowList[$item->id] = [
                'id'        => $item->id,
                'name'      => $item->name,
                'photo'     => $photoPath,
                'qty'       => $qty,
                'total_qty' => (int) $item->total_qty,
                'safe_max_qty' => $safeBorrowLimit,
                'available_qty' => (int) $item->available_qty,
                'category'  => $item->category,
            ];
        }

        Session::put('borrowList', $borrowList);
        return back()->with('success', "{$item->name} added to Borrow List.");
    }

    // ✅ Remove from Borrow List
    public function removeFromBorrowList(Item $item)
    {
        $borrowList = Session::get('borrowList', []);
        unset($borrowList[$item->id]);
        Session::put('borrowList', $borrowList);
        return back();
    }

    // ✅ Submit borrow request
    public function submitRequest(Request $request)
    {
        $borrowList = Session::get('borrowList', []);
        if (empty($borrowList)) {
            return back()->withErrors(['borrowList' => 'Your Borrow List is empty.']);
        }

        $postedItems = $request->input('items', []);
        foreach ($borrowList as $itemId => &$listItem) {
            if (!isset($postedItems[$itemId]['quantity'])) {
                continue;
            }
            $safeMax = (int) ($listItem['safe_max_qty'] ?? 0);
            $maxAllowed = $safeMax > 0 ? $safeMax : max(1, (int) ($listItem['total_qty'] ?? 1));
            $requestedQty = (int) $postedItems[$itemId]['quantity'];
            if ($requestedQty < 1) {
                $requestedQty = 1;
            }
            if ($requestedQty > $maxAllowed) {
                $requestedQty = $maxAllowed;
            }
            $listItem['qty'] = $requestedQty;
        }
        unset($listItem);
        Session::put('borrowList', $borrowList);

        $validated = $request->validate([
            'borrow_date'     => 'required|date|after_or_equal:today',
            'return_date'     => 'required|date|after_or_equal:borrow_date',
            // Time of usage is optional
            'time_of_usage'   => 'nullable|string|max:50',
            'location'        => 'required|string|max:255',
            'purpose_office'  => 'required|string|max:255',
            'purpose'         => 'required|string|max:500',
            'support_letter'  => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ], [
            'support_letter.required' => 'Please upload your signed letter before proceeding.',
            'support_letter.mimes'    => 'The letter must be an image or PDF file.',
        ]);

        [$borrowDate, $returnDate] = [$validated['borrow_date'], $validated['return_date']];

        $items = Item::query()
            ->withCount([
                'instances as non_borrowable_instances_count' => function ($query) {
                    $query->whereIn('status', $this->nonBorrowableInstanceStatuses);
                },
            ])
            ->whereIn('id', array_keys($borrowList))
            ->get();

        foreach ($items as $item) {
            $requestedQty = $borrowList[$item->id]['qty'];
            $safeBorrowLimit = $this->getSafeBorrowLimit($item);

            $alreadyReserved = BorrowRequestItem::where('item_id', $item->id)
                ->whereHas('request', function ($q) use ($borrowDate, $returnDate) {
                    $q->whereIn('status', ['pending', 'validated', 'approved', 'return_pending'])
                      ->where('borrow_date', '<=', $returnDate)
                      ->where('return_date', '>=', $borrowDate);
                })
                ->sum('quantity');

            if ($requestedQty + $alreadyReserved > $safeBorrowLimit) {
                $remaining = max(0, $safeBorrowLimit - $alreadyReserved);
                return back()->withErrors([
                    'date' => 'Not enough ' . $item->name . ' available in this date range.'
                ])->withInput();
            }
        }

        $letterPath = null;
        if ($request->hasFile('support_letter')) {
            $letterPath = $request->file('support_letter')->store('borrow-letters', 'public');
        }

        // Normalize time_of_usage to null if blank
        $timeOfUsage = trim((string)($validated['time_of_usage'] ?? $request->input('time_of_usage', '')));
        if ($timeOfUsage === '') {
            $timeOfUsage = null;
        }

        $borrowRequest = BorrowRequest::create([
            'user_id'        => Auth::id(),
            'borrow_date'    => $borrowDate,
            'return_date'    => $returnDate,
            'time_of_usage'  => $timeOfUsage,
            'purpose_office' => $validated['purpose_office'],
            'purpose'        => $validated['purpose'],
            'location'       => $validated['location'],
            'letter_path'    => $letterPath,
            'status'         => 'pending',
        ]);

        foreach ($borrowList as $itemId => $listItem) {
            BorrowRequestItem::create([
                'borrow_request_id' => $borrowRequest->id,
                'item_id'           => $itemId,
                'quantity'          => $listItem['qty'],
            ]);
        }

        Session::forget('borrowList');

        $admins = User::where('role', 'admin')->get();

        $itemsPayload = [];
        foreach ($borrowList as $itemId => $listItem) {
            $itemsPayload[] = [
                'id' => $itemId,
                'name' => $listItem['name'],
                'quantity' => $listItem['qty'],
            ];
        }

        $payload = [
            'type' => 'borrow_submitted',
            'message' => "New borrow request #{$borrowRequest->id} submitted by " . ($request->user()->first_name ?? 'Someone'),
            'borrow_request_id' => $borrowRequest->id,
            'user_id' => $request->user()->id,
            'user_name' => trim($request->user()->first_name . ' ' . ($request->user()->last_name ?? '')),
            'time_of_usage' => $timeOfUsage,
            'purpose_office' => $validated['purpose_office'],
            'purpose' => $validated['purpose'],
            'items' => $itemsPayload,
            'borrow_date' => $borrowRequest->borrow_date,
            'return_date' => $borrowRequest->return_date,
        ];

        Notification::send($admins, new RequestNotification($payload));

        return redirect()->route('borrow.items')
            ->with('success', 'Borrow request submitted successfully!');
    }

    /**
     * Availability API
     *
     * - If borrow_date and return_date are provided (query string), returns:
     *     { available: bool, remaining: int, already_reserved: int, message: string }
     * - Otherwise, falls back to the original behaviour and returns an array of unavailable dates.
     */
    public function availability(Request $request, Item $item)
    {
        $borrowDate = $request->query('borrow_date');
        $returnDate = $request->query('return_date');
        $requestedQty = (int) $request->query('qty', 0);

        $item->loadCount([
            'instances as non_borrowable_instances_count' => function ($query) {
                $query->whereIn('status', $this->nonBorrowableInstanceStatuses);
            },
        ]);
        $safeBorrowLimit = $this->getSafeBorrowLimit($item);

        // If the caller provided a date range, return availability + remaining qty
        if ($borrowDate && $returnDate) {
            // Validate and parse dates safely
            try {
                $b = \Carbon\Carbon::parse($borrowDate)->startOfDay();
                $r = \Carbon\Carbon::parse($returnDate)->endOfDay();

                if ($b->gt($r)) {
                    return response()->json([
                        'available' => false,
                        'remaining' => 0,
                        'already_reserved' => 0,
                        'message' => 'Borrow date must be before or equal to return date.'
                    ], 422);
                }
            } catch (\Throwable $e) {
                return response()->json([
                    'available' => false,
                    'remaining' => 0,
                    'already_reserved' => 0,
                    'message' => 'Invalid date format.'
                ], 422);
            }

        // Sum quantities of overlapping dispatched/delivered requests for that item
        // Only dispatched/delivered requests are considered as actually using stock (per requirement)
        $alreadyReserved = BorrowRequestItem::where('item_id', $item->id)
                ->whereHas('request', function ($q) use ($borrowDate, $returnDate) {
                    $q->whereIn('delivery_status', ['dispatched', 'delivered'])
                      ->whereIn('status', ['approved', 'return_pending', 'returned'])
                      ->where('borrow_date', '<=', $returnDate)
                      ->where('return_date', '>=', $borrowDate);
                })
                ->sum('quantity');

            $remaining = max(0, $safeBorrowLimit - (int) $alreadyReserved);
            $available = $remaining >= $requestedQty;

            return response()->json([
                'available' => $available,
                'remaining' => $remaining,
                'already_reserved' => (int) $alreadyReserved,
                'message' => $available
                    ? 'Available'
                    : 'Not enough ' . $item->name . ' available in this date range.'
            ]);
        }

        // No date range requested: fallback to previous behavior (return unavailable dates)
        // Only consider dispatched/delivered requests as actually using stock (per requirement)
        $requests = BorrowRequestItem::where('item_id', $item->id)
            ->whereHas('request', function ($q) {
                $q->whereIn('delivery_status', ['dispatched', 'delivered'])
                  ->whereIn('status', ['approved', 'return_pending', 'returned']);
            })
            ->with('request')
            ->get();

        $counts = [];
        foreach ($requests as $reqItem) {
            $borrow = optional($reqItem->request)->borrow_date;
            $return = optional($reqItem->request)->return_date;
            if (! $borrow || ! $return) continue;

            $period = new \DatePeriod(
                new \DateTime($borrow),
                new \DateInterval('P1D'),
                (new \DateTime($return))->modify('+1 day')
            );

            foreach ($period as $date) {
                $d = $date->format("Y-m-d");
                if (! isset($counts[$d])) $counts[$d] = 0;
                $counts[$d] += $reqItem->quantity;
            }
        }

        $unavailableDates = [];
        foreach ($counts as $date => $qty) {
            if ($qty >= $safeBorrowLimit) {
                $unavailableDates[] = $date;
            }
        }

        return response()->json($unavailableDates);
    }

    /**
     * Availability API for multiple items
     * Returns dates that are booked (where any item has insufficient stock)
     * 
     * Expected query parameters:
     * - items: JSON array of {item_id, quantity} objects
     */
    public function availabilityMultiple(Request $request)
    {
        $itemsJson = $request->query('items', '[]');
        $items = json_decode($itemsJson, true);
        
        if (!is_array($items) || empty($items)) {
            return response()->json([]);
        }

        // Get all item IDs and quantities
        $itemMap = [];
        foreach ($items as $itemData) {
            $itemId = (int) ($itemData['item_id'] ?? 0);
            $quantity = (int) ($itemData['quantity'] ?? 0);
            if ($itemId > 0 && $quantity > 0) {
                $itemMap[$itemId] = $quantity;
            }
        }

        if (empty($itemMap)) {
            return response()->json([]);
        }

        // Load items from database
        $dbItems = Item::query()
            ->withCount([
                'instances as non_borrowable_instances_count' => function ($query) {
                    $query->whereIn('status', $this->nonBorrowableInstanceStatuses);
                },
            ])
            ->whereIn('id', array_keys($itemMap))
            ->get()
            ->keyBy('id');
        
        // Get all borrowed items for these items (only dispatched/delivered)
        $requests = BorrowRequestItem::whereIn('item_id', array_keys($itemMap))
            ->whereHas('request', function ($q) {
                $q->whereIn('delivery_status', ['dispatched', 'delivered'])
                  ->whereIn('status', ['approved', 'return_pending', 'returned']);
            })
            ->with('request')
            ->get();

        // Build date counts per item
        $itemCounts = [];
        foreach ($itemMap as $itemId => $requestedQty) {
            $itemCounts[$itemId] = [];
        }

        foreach ($requests as $reqItem) {
            $itemId = $reqItem->item_id;
            if (!isset($itemCounts[$itemId])) continue;

            $borrow = optional($reqItem->request)->borrow_date;
            $return = optional($reqItem->request)->return_date;
            if (! $borrow || ! $return) continue;

            $period = new \DatePeriod(
                new \DateTime($borrow),
                new \DateInterval('P1D'),
                (new \DateTime($return))->modify('+1 day')
            );

            foreach ($period as $date) {
                $d = $date->format("Y-m-d");
                if (! isset($itemCounts[$itemId][$d])) {
                    $itemCounts[$itemId][$d] = 0;
                }
                $itemCounts[$itemId][$d] += $reqItem->quantity;
            }
        }

        // Find dates where ANY item has insufficient stock
        $bookedDates = [];
        foreach ($itemCounts as $itemId => $counts) {
            $item = $dbItems[$itemId] ?? null;
            if (!$item) continue;

            $totalQty = $this->getSafeBorrowLimit($item);
            $requestedQty = $itemMap[$itemId];

            foreach ($counts as $date => $borrowedQty) {
                $remaining = max(0, $totalQty - $borrowedQty);
                if ($remaining < $requestedQty) {
                    if (!in_array($date, $bookedDates)) {
                        $bookedDates[] = $date;
                    }
                }
            }

            // Also check if total stock is less than requested (item permanently unavailable)
            if ($totalQty < $requestedQty) {
                // This would require checking all dates, but for now we'll let the calendar handle it
            }
        }

        sort($bookedDates);
        return response()->json($bookedDates);
    }


    /**
     * User confirms they received the items.
     */
    public function confirmDelivery(Request $request, BorrowRequest $borrowRequest)
    {
        if ($borrowRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($borrowRequest->delivery_status === 'delivered') {
            return response()->json(['message' => 'Already confirmed delivered.'], 200);
        }

        $borrowRequest->markDelivered();

        // Notify admins
        $admins = User::where('role', 'admin')->get();
        $payload = [
            'type' => 'delivery_confirmed',
            'message' => "User confirmed receipt for borrow request #{$borrowRequest->id}.",
            'borrow_request_id' => $borrowRequest->id,
            'user_id' => $borrowRequest->user_id,
            'user_name' => trim($borrowRequest->user->first_name . ' ' . ($borrowRequest->user->last_name ?? '')),
            'delivered_at' => $borrowRequest->delivered_at ? $borrowRequest->delivered_at->toDateTimeString() : null,
        ];
        Notification::send($admins, new RequestNotification($payload));

        return response()->json(['message' => 'Confirmed receipt.']);
    }

    /**
     * User reports that they did not receive the dispatched items.
     */
    public function reportNotReceived(Request $request, BorrowRequest $borrowRequest)
    {
        if ($borrowRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:2000',
        ]);

        $reason = $request->input('reason');
        $borrowRequest->markNotReceived($reason);

        // Notify admins
        $admins = User::where('role', 'admin')->get();
        $payload = [
            'type' => 'delivery_reported',
            'message' => "User reported not receiving borrow request #{$borrowRequest->id}.",
            'borrow_request_id' => $borrowRequest->id,
            'user_id' => $borrowRequest->user_id,
            'user_name' => trim($borrowRequest->user->first_name . ' ' . ($borrowRequest->user->last_name ?? '')),
            'reason' => $reason,
            'reported_at' => $borrowRequest->delivery_reported_at ? $borrowRequest->delivery_reported_at->toDateTimeString() : null,
        ];
        Notification::send($admins, new RequestNotification($payload));

        return response()->json(['message' => 'Reported not received.']);
    }

    /**
     * Render a PDF (dompdf) of the borrow request (printable pickup slip).
     * - Accessible to request owner and admins.
     */
    public function print(BorrowRequest $borrowRequest, BorrowRequestFormPdf $borrowRequestFormPdf)
    {
        $user = Auth::user();
        // allow admin or owner
        if (! $user || ($user->role !== 'admin' && $borrowRequest->user_id !== $user->id)) {
            abort(403);
        }

        $result = $borrowRequestFormPdf->render($borrowRequest);

        return response($result['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $result['filename'] . '"',
            'Content-Length' => strlen($result['content']),
        ]);
    }
}
