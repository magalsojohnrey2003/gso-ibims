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
use Barryvdh\DomPDF\Facade\Pdf; 

class BorrowItemsController extends Controller
{
    private array $defaultPhotos = [
        'furniture'   => 'images/defaults_category_photo/furniture.png',
        'electronics' => 'images/defaults_category_photo/electronics.png',
        'tools'       => 'images/defaults_category_photo/tools.png',
        'vehicles'    => 'images/defaults_category_photo/vehicles.png',
    ];

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $items = Item::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
            })
            ->get();

        $borrowList = Session::get('borrowList', []);

        // Remove deleted items automatically
        foreach ($borrowList as $id => $listItem) {
            if (!Item::where('id', $id)->exists()) {
                unset($borrowList[$id]);
            }
        }
        Session::put('borrowList', $borrowList);

        return view('user.borrow-items.index', [
            'items'            => $items,
            'borrowList'       => $borrowList,
            'borrowListCount'  => count($borrowList),
            'defaultPhotos'    => $this->defaultPhotos,
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
        Session::put('borrowList', $borrowList);

        return view('user.borrow-items.borrowList', [
            'borrowList'      => $borrowList,
            'borrowListCount' => count($borrowList),
            'defaultPhotos'   => $this->defaultPhotos,
        ]);
    }

    // ✅ Add to Borrow List
    public function addToBorrowList(Request $request, Item $item)
    {
        if ($item->total_qty <= 0) {
            return back()->withErrors(['item' => "This item \"{$item->name}\" is out of stock and cannot be added."]);
        }

        $qty = max(1, (int) $request->input('qty', 1));
        $qty = min($qty, $item->total_qty);

        $borrowList = Session::get('borrowList', []);

        $photoPath = $item->photo
            ? 'storage/' . $item->photo
            : $this->defaultPhotos[$item->category] ?? 'images/no-image.png';

        if (isset($borrowList[$item->id])) {
            $newQty = min($borrowList[$item->id]['qty'] + $qty, $item->total_qty);
            if ($newQty === $borrowList[$item->id]['qty']) {
                return back()->withErrors(['item' => "You already have the maximum ({$item->total_qty}) of {$item->name} in your list."]);
            }
            $borrowList[$item->id]['qty'] = $newQty;
        } else {
            $borrowList[$item->id] = [
                'id'        => $item->id,
                'name'      => $item->name,
                'photo'     => $photoPath,
                'qty'       => $qty,
                'total_qty' => $item->total_qty,
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

        $validated = $request->validate([
            'borrow_date'    => 'required|date|after_or_equal:today',
            'return_date'    => 'required|date|after_or_equal:borrow_date',
            'manpower_count' => 'nullable|integer|min:1|max:100',
        ]);

        [$borrowDate, $returnDate] = [$validated['borrow_date'], $validated['return_date']];

        $items = Item::whereIn('id', array_keys($borrowList))->get();

        foreach ($items as $item) {
            $requestedQty = $borrowList[$item->id]['qty'];

            $alreadyReserved = BorrowRequestItem::where('item_id', $item->id)
                ->whereHas('request', function ($q) use ($borrowDate, $returnDate) {
                    $q->whereIn('status', ['pending', 'approved'])
                      ->where('borrow_date', '<=', $returnDate)
                      ->where('return_date', '>=', $borrowDate);
                })
                ->sum('quantity');

            if ($requestedQty + $alreadyReserved > $item->total_qty) {
                $remaining = max(0, $item->total_qty - $alreadyReserved);
                return back()->withErrors([
                    'date' => 'Not enough ' . $item->name . ' available in this date range.'
                ])->withInput();
            }
        }

        $borrowRequest = BorrowRequest::create([
            'user_id'        => Auth::id(),
            'borrow_date'    => $borrowDate,
            'return_date'    => $returnDate,
            'manpower_count' => $request->input('manpower_count'),
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

            // Sum quantities of overlapping pending/approved requests for that item
            $alreadyReserved = BorrowRequestItem::where('item_id', $item->id)
                ->whereHas('request', function ($q) use ($borrowDate, $returnDate) {
                    $q->whereIn('status', ['pending', 'approved'])
                    ->where('borrow_date', '<=', $returnDate)
                    ->where('return_date', '>=', $borrowDate);
                })
                ->sum('quantity');

            $remaining = max(0, (int) $item->total_qty - (int) $alreadyReserved);
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
        $requests = BorrowRequestItem::where('item_id', $item->id)
            ->whereHas('request', function ($q) {
                $q->whereIn('status', ['pending', 'approved']);
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
            if ($qty >= $item->total_qty) {
                $unavailableDates[] = $date;
            }
        }

        return response()->json($unavailableDates);
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
    public function print(BorrowRequest $borrowRequest)
    {
        $user = Auth::user();
        // allow admin or owner
        if (! $user || ($user->role !== 'admin' && $borrowRequest->user_id !== $user->id)) {
            abort(403);
        }

        $borrowRequest->load(['user', 'items.item', 'borrowedInstances.instance', 'borrowedInstances.item']);

        $pdf = Pdf::loadView('pdf.borrow-request', ['borrowRequest' => $borrowRequest])
            ->setPaper('a4', 'portrait');

        // inline so browser opens the PDF
        return $pdf->stream("borrow-request-{$borrowRequest->id}.pdf");
    }
}
