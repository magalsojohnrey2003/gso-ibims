<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\Item;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalItems       = Item::count();
        $availableItems   = Item::sum('available_qty');
        $totalBorrowReq   = BorrowRequest::count();
        $pendingReq       = BorrowRequest::where('status', 'pending')->count();
        $approvedReq      = BorrowRequest::where('status', 'approved')->count();
        $rejectedReq      = BorrowRequest::where('status', 'rejected')->count();

        $borrowTrends = BorrowRequest::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as label"),
                DB::raw("COUNT(*) as total")
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('label')
            ->orderBy('label')
            ->pluck('total', 'label')
            ->toArray();

        $itemUsage = BorrowRequestItem::select('item_id', DB::raw('SUM(quantity) as total'))
            ->whereHas('request', fn($q) => $q->where('status','approved'))
            ->with('item')
            ->groupBy('item_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(fn($row) => [
                'name'  => $row->item->name ?? 'Unknown',
                'total' => (int) $row->total
            ]);

        $categories = Item::query()->distinct('category')->pluck('category')->filter()->values()->toArray();

        return view('admin.dashboard', compact(
            'totalItems',
            'availableItems',
            'totalBorrowReq',
            'pendingReq',
            'approvedReq',
            'rejectedReq',
            'borrowTrends',
            'itemUsage',
            'categories'
        ));
    }

    public function borrowTrends(Request $request)
    {
        $range = $request->query('range');
        $months = $request->query('months');

        if ($months && is_numeric($months)) {
            $months = (int) $months;
            $start = now()->subMonths($months);
            $rows = BorrowRequest::select(
                        DB::raw("DATE_FORMAT(created_at, '%Y-%m') as label"),
                        DB::raw("COUNT(*) as total")
                    )
                    ->where('status','approved')
                    ->where('created_at', '>=', $start)
                    ->groupBy('label')
                    ->orderBy('label')
                    ->pluck('total', 'label')
                    ->toArray();

            $labels = [];
            $cursor = $start->copy()->startOfMonth();
            $end = now()->startOfMonth();
            while ($cursor->lte($end)) {
                $key = $cursor->format('Y-m');
                $labels[$key] = $rows[$key] ?? 0;
                $cursor->addMonth();
            }

            return response()->json($labels);
        }

        $range = $range ? strtolower($range) : 'month';

        if ($range === 'week') {
            $start = now()->subDays(6)->startOfDay();
            $rows = BorrowRequest::select(
                        DB::raw("DATE(created_at) as label"),
                        DB::raw("COUNT(*) as total")
                    )
                    ->where('status','approved')
                    ->whereBetween('created_at', [$start, now()])
                    ->groupBy('label')
                    ->orderBy('label')
                    ->pluck('total', 'label')
                    ->toArray();

            $labels = [];
            for ($d = $start->copy(); $d->lte(now()); $d->addDay()) {
                $k = $d->toDateString();
                $labels[$k] = $rows[$k] ?? 0;
            }

            return response()->json($labels);
        }

        if ($range === 'year') {
            $start = now()->subMonths(11)->startOfMonth();
            $rows = BorrowRequest::select(
                        DB::raw("DATE_FORMAT(created_at, '%Y-%m') as label"),
                        DB::raw("COUNT(*) as total")
                    )
                    ->where('status','approved')
                    ->whereBetween('created_at', [$start, now()])
                    ->groupBy('label')
                    ->orderBy('label')
                    ->pluck('total', 'label')
                    ->toArray();

            $labels = [];
            for ($m = $start->copy(); $m->lte(now()); $m->addMonth()) {
                $k = $m->format('Y-m');
                $labels[$k] = $rows[$k] ?? 0;
            }

            return response()->json($labels);
        }

        $start = now()->subDays(29)->startOfDay();
        $rows = BorrowRequest::select(
                    DB::raw("DATE(created_at) as label"),
                    DB::raw("COUNT(*) as total")
                )
                ->where('status','approved')
                ->whereBetween('created_at', [$start, now()])
                ->groupBy('label')
                ->orderBy('label')
                ->pluck('total', 'label')
                ->toArray();

        $labels = [];
        for ($d = $start->copy(); $d->lte(now()); $d->addDay()) {
            $k = $d->toDateString();
            $labels[$k] = $rows[$k] ?? 0;
        }

        return response()->json($labels);
    }

    /**
     * Most borrowed items (optional category filter)
     * Returns: [{ name, total }, ...]
     */
    public function mostBorrowed(Request $request)
    {
        $query = BorrowRequestItem::select('item_id', DB::raw('SUM(quantity) as total'))
            ->whereHas('request', fn($q) => $q->where('status','approved'))
            ->with('item')
            ->groupBy('item_id')
            ->orderByDesc('total');

        if ($request->filled('category')) {
            $category = $request->category;
            $query->whereHas('item', fn($q) => $q->where('category', $category));
        }

        $results = $query->take(10)->get()
            ->map(fn($r) => ['name' => $r->item->name ?? 'Unknown', 'total' => (int)$r->total])
            ->values();

        return response()->json($results);
    }

    public function activity()
    {
        $borrows = BorrowRequest::with(['user', 'items.item'])
            ->latest()
            ->take(15)
            ->get()
            ->map(function ($b) {
                return [
                    'type' => 'borrow',
                    'user' => $b->user?->first_name . ' ' . ($b->user?->last_name ?? ''),
                    'time' => $b->created_at?->diffForHumans() ?? '',
                    'created_at' => $b->created_at,
                    'action' => 'Borrow request (' . ($b->status ?? 'N/A') . ')',
                    'items' => $b->items->map(fn($it) => ($it->item?->name ?? 'Unknown') . ' x' . $it->quantity)->implode(', ')
                ];
            });

        $returns = ReturnRequest::with(['user', 'borrowRequest.items.item'])
            ->latest()
            ->take(15)
            ->get()
            ->map(function ($r) {
                return [
                    'type' => 'return',
                    'user' => $r->user?->first_name . ' ' . ($r->user?->last_name ?? ''),
                    'time' => $r->created_at?->diffForHumans() ?? '',
                    'created_at' => $r->created_at,
                    'action' => 'Return request (' . ($r->status ?? 'N/A') . ')',
                    'items' => $r->borrowRequest?->items->map(fn($it) => ($it->item?->name ?? 'Unknown') . ' x' . $it->quantity)->implode(', ')
                ];
            });

        $merged = $borrows->concat($returns)
            ->sortByDesc(fn($l) => $l['created_at'])
            ->values()
            ->take(20)
            ->map(function ($l) {
                return [
                    'user' => $l['user'] ?? 'Unknown',
                    'time' => $l['time'] ?? '',
                    'action' => $l['action'] ?? '',
                    'items' => $l['items'] ?? ''
                ];
            });

        return response()->json($merged);
    }
}
