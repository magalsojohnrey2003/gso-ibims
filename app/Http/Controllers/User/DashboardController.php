<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // === Stats ===
        $myBorrowedCount = BorrowRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->count();

        $pendingReq = BorrowRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $returnedReq = BorrowRequest::where('user_id', $user->id)
            ->where('status', 'returned')
            ->count();

        $rejectedReq = BorrowRequest::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->count();

        // === My Borrow Requests (with items) ===
        $myRequests = BorrowRequest::with('items.item')
            ->where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        // === Borrow Trends (last 30 days default) ===
        $myBorrowTrends = BorrowRequest::select(
                DB::raw("DATE(created_at) as day"),
                DB::raw("COUNT(*) as total")
            )
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->toArray();

        // === Available items preview (just 4) ===
        $availableItemsPreview = Item::where('available_qty', '>', 0)
            ->take(4)
            ->get(['id', 'name', 'category', 'photo'])
            ->toArray();

        // === Recent activity (last 5 requests) ===
        $recentActivity = $myRequests->map(function ($req) {
            return [
                'action' => ucfirst($req->status),
                'time'   => Carbon::parse($req->created_at)->diffForHumans(),
            ];
        })->toArray();

        return view('user.dashboard', compact(
            'myBorrowedCount',
            'pendingReq',
            'returnedReq',
            'rejectedReq',
            'myRequests',
            'myBorrowTrends',
            'availableItemsPreview',
            'recentActivity'
        ));
    }

    // === API Endpoints ===

    // Get my requests (JSON)
    public function myRequests()
    {
        $requests = BorrowRequest::with('items.item')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json($requests);
    }

    // Cancel a pending request
    public function cancelRequest($id)
    {
        $req = BorrowRequest::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $req->status = 'cancelled';
        $req->save();

        return response()->json(['success' => true]);
    }

    // Request return
    public function requestReturn($id)
    {
        $req = BorrowRequest::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status', 'approved')
            ->firstOrFail();

        $req->status = 'return_requested';
        $req->save();

        return response()->json(['success' => true]);
    }

    // Borrow trends filter
    public function borrowTrends(Request $request)
    {
        $range = $request->get('range', 'month');
        $query = BorrowRequest::where('user_id', Auth::id());

        if ($range === 'week') {
            $query->where('created_at', '>=', now()->subDays(7))
                  ->select(DB::raw("DATE(created_at) as label"), DB::raw("COUNT(*) as total"))
                  ->groupBy('label');
        } elseif ($range === 'year') {
            $query->where('created_at', '>=', now()->subMonths(12))
                  ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as label"), DB::raw("COUNT(*) as total"))
                  ->groupBy('label');
        } else {
            // default month (30 days)
            $query->where('created_at', '>=', now()->subDays(30))
                  ->select(DB::raw("DATE(created_at) as label"), DB::raw("COUNT(*) as total"))
                  ->groupBy('label');
        }

        $data = $query->orderBy('label')->pluck('total', 'label');

        return response()->json($data);
    }

    // Recent activity (JSON)
    public function activity()
    {
        $logs = BorrowRequest::where('user_id', Auth::id())
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($req) {
                return [
                    'action' => ucfirst($req->status),
                    'time'   => Carbon::parse($req->created_at)->diffForHumans(),
                ];
            });

        return response()->json($logs);
    }

    // Available items preview
    public function availableItems()
    {
        $items = Item::where('available_qty', '>', 0)
            ->take(6)
            ->get(['id', 'name', 'category', 'photo']);

        return response()->json($items);
    }
}
