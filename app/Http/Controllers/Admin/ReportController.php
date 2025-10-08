<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportsExport;
use Throwable;

class ReportController extends Controller
{
    protected array $availableReports = [
        'inventory_summary' => 'Inventory Summary',
        'low_stock' => 'Low Stock Items',
        'top_borrowed' => 'Top Borrowed Items',
        'borrow_by_status' => 'Borrow Requests by Status',
        'borrow_activity' => 'Borrow Activity Over Time',
        'active_borrows' => 'Active Borrowings',
        'overdue_borrows' => 'Overdue Borrows',
        'return_requests' => 'Return Requests Summary',
        'top_users' => 'Top Users',
        'category_summary' => 'Category Summary',
        'manpower_usage' => 'Manpower Usage',
    ];

    public function index()
    {
        $reports = $this->availableReports;
        return view('admin.reports.index', compact('reports'));
    }

    /**
     * Return JSON for the requested report and period.
     * Expects POST payload: report_type, period, from, to, threshold
     */
    public function data(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:' . implode(',', array_keys($this->availableReports)),
            'period' => 'required|in:week,month,year,custom',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'threshold' => 'nullable|integer'
        ]);

        [$start, $end] = $this->getRange($validated['period'], $validated['from'] ?? null, $validated['to'] ?? null);

        $report = $this->generateReport($validated['report_type'], $start, $end, $validated);

        $meta = $this->buildMeta($validated['report_type'], $start, $end);

        $extra = $report['extra'] ?? [];
        $extra['kpis'] = $extra['kpis'] ?? [];

        return response()->json([
            'columns' => $report['columns'] ?? [],
            'rows'    => $report['rows'] ?? [],
            'meta'    => $meta,
            'extra'   => $extra,
        ]);
    }

    public function exportPdf(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            [$start, $end] = $this->getRange($validated['period'], $validated['from'] ?? null, $validated['to'] ?? null);
            $reportData = $this->generateReport($validated['report_type'], $start, $end, $validated);

            $meta = $this->buildMeta($validated['report_type'], $start, $end);

            $columns = $reportData['columns'] ?? [];
            $rows = $reportData['rows'] ?? [];

            return Pdf::loadView('admin.reports.pdf', [
                'columns' => $columns,
                'rows'    => $rows,
                'meta'    => $meta,
            ])->setPaper('a4', 'landscape')->download($this->fileName($meta, 'pdf'));
        } catch (Throwable $e) {
            logger()->error('PDF export failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to generate PDF', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET export XLSX (querystring expected)
     */
    public function exportXlsx(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            [$start, $end] = $this->getRange($validated['period'], $validated['from'] ?? null, $validated['to'] ?? null);
            $reportData = $this->generateReport($validated['report_type'], $start, $end, $validated);

            $meta = $this->buildMeta($validated['report_type'], $start, $end);

            $columns = $reportData['columns'] ?? [];
            $rows = $reportData['rows'] ?? [];

            return Excel::download(new ReportsExport($columns, $rows, $meta), $this->fileName($meta, 'xlsx'));
        } catch (Throwable $e) {
            logger()->error('XLSX export failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to generate XLSX', 'error' => $e->getMessage()], 500);
        }
    }

    protected function validateRequest(Request $request): array
    {
        return $request->validate([
            'report_type' => 'required|in:' . implode(',', array_keys($this->availableReports)),
            'period' => 'required|in:week,month,year,custom',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'threshold' => 'nullable|integer'
        ]);
    }

    protected function buildMeta(string $reportType, Carbon $start, Carbon $end): array
    {
        return [
            'title' => $this->availableReports[$reportType] ?? 'Report',
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'generated_at' => Carbon::now()->toDateTimeString(),
        ];
    }

    protected function fileName(array $meta, string $ext): string
    {
        $base = str_replace(' ', '_', strtolower($meta['title'] ?? 'report'));
        return "{$base}_{$meta['start']}_to_{$meta['end']}.{$ext}";
    }

    protected function getRange(string $period, $from = null, $to = null): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'week':
                $start = (clone $now)->startOfWeek();
                $end   = (clone $now)->endOfWeek();
                break;
            case 'month':
                $start = (clone $now)->startOfMonth();
                $end   = (clone $now)->endOfMonth();
                break;
            case 'year':
                $start = (clone $now)->startOfYear();
                $end   = (clone $now)->endOfYear();
                break;
            case 'custom':
                $start = $from ? Carbon::parse($from)->startOfDay() : (clone $now)->startOfMonth();
                $end   = $to ? Carbon::parse($to)->endOfDay() : (clone $now)->endOfDay();
                break;
            default:
                $start = (clone $now)->startOfMonth();
                $end   = (clone $now)->endOfMonth();
                break;
        }

        return [$start, $end];
    }

    /**
     * Build the actual report columns & rows (rows are arrays that align with columns)
     */
    protected function generateReport(string $type, Carbon $start, Carbon $end, array $opts = []): array
    {
        switch ($type) {
            case 'inventory_summary':
                $items = Item::select('id','name','category','total_qty','available_qty')
                    ->orderBy('category')->get();

                $columns = ['ID','Name','Category','Total Qty','Available Qty'];
                $rows = $items->map(fn($i) => [
                    $i->id,
                    $i->name,
                    $i->category,
                    (int)$i->total_qty,
                    (int)$i->available_qty,
                ])->toArray();

                $extra = ['kpis'=>[
                    ['label'=>'Total Items','value'=> $items->count()],
                    ['label'=>'Total Stock','value'=> $items->sum(fn($i)=> (int)$i->total_qty)]
                ]];

                return compact('columns','rows','extra');

            case 'low_stock':
                $threshold = isset($opts['threshold']) ? (int)$opts['threshold'] : 5;
                $q = Item::where('available_qty', '<=', $threshold)
                         ->orderBy('available_qty','asc')->get();

                $columns = ['ID','Name','Category','Total Qty','Available Qty'];
                $rows = $q->map(fn($i)=>[
                    $i->id, $i->name, $i->category, (int)$i->total_qty, (int)$i->available_qty
                ])->toArray();

                $extra = ['kpis'=>[['label'=>'Threshold','value'=>$threshold], ['label'=>'Low stock count','value'=>$q->count()]]];

                return compact('columns','rows','extra');

            case 'top_borrowed':
                $items = BorrowRequestItem::select('item_id', DB::raw('SUM(quantity) as total'))
                    ->whereHas('request', function ($q) use ($start, $end) {
                        $q->whereBetween('borrow_date', [$start->toDateString(), $end->toDateString()])
                          ->whereIn('status', ['approved','returned','pending']);
                    })
                    ->groupBy('item_id')
                    ->orderByDesc('total')
                    ->with('item')
                    ->get();

                $columns = ['Item ID','Item Name','Category','Total Borrowed'];
                $rows = $items->map(fn($r)=>[
                    $r->item_id,
                    $r->item?->name ?? 'Unknown',
                    $r->item?->category ?? 'Unknown',
                    (int)$r->total,
                ])->toArray();

                return compact('columns','rows');

            case 'borrow_by_status':
                $counts = BorrowRequest::select('status', DB::raw('COUNT(*) as total'))
                    ->whereBetween('borrow_date', [$start->toDateString(), $end->toDateString()])
                    ->groupBy('status')->get();

                $columns = ['Status','Count'];
                $rows = $counts->map(fn($c)=>[$c->status, (int)$c->total])->toArray();

                return compact('columns','rows');

            case 'borrow_activity':
                $rowsRaw = DB::table('borrow_requests')
                    ->select(DB::raw('DATE(borrow_date) as date'), DB::raw('COUNT(*) as total'))
                    ->whereBetween('borrow_date', [$start->toDateString(), $end->toDateString()])
                    ->groupBy(DB::raw('DATE(borrow_date)'))
                    ->orderBy('date')
                    ->get();

                $columns = ['Date','Requests'];
                $rows = $rowsRaw->map(fn($r)=>[$r->date, (int)$r->total])->toArray();

                return compact('columns','rows');

            case 'active_borrows':
                $today = Carbon::now()->toDateString();
                $active = BorrowRequest::where('status','approved')
                    ->whereDate('borrow_date', '<=', $today)
                    ->whereDate('return_date', '>=', $today)
                    ->with('items.item','user')
                    ->get();

                $columns = ['Request ID','User','Borrow Date','Return Date','Items Count'];
                $rows = $active->map(fn($b)=>[
                    $b->id,
                    $b->user?->getFullNameAttribute() ?? $b->user_id,
                    $b->borrow_date,
                    $b->return_date,
                    $b->items->sum('quantity'),
                ])->toArray();

                return compact('columns','rows');

            case 'overdue_borrows':
                $today = Carbon::now()->toDateString();
                $over = BorrowRequest::where('status','approved')
                    ->whereDate('return_date', '<', $today)
                    ->with('user','items.item')
                    ->get();

                $columns = ['Request ID','User','Return Date','Days Overdue','Items Count'];
                $rows = $over->map(fn($b)=>[
                    $b->id,
                    $b->user?->getFullNameAttribute() ?? $b->user_id,
                    $b->return_date,
                    Carbon::parse($b->return_date)->diffInDays($today),
                    $b->items->sum('quantity'),
                ])->toArray();

                return compact('columns','rows');

            case 'return_requests':
                $counts = ReturnRequest::select('status', DB::raw('COUNT(*) as total'))
                    ->whereBetween('created_at', [$start->toDateString(), $end->toDateString()])
                    ->groupBy('status')
                    ->get();

                $columns = ['Status','Count'];
                $rows = $counts->map(fn($c)=>[$c->status,(int)$c->total])->toArray();

                return compact('columns','rows');

            case 'top_users':
                $users = BorrowRequest::select('user_id', DB::raw('COUNT(*) as total_requests'), DB::raw('SUM(manpower_count) as total_manpower'))
                    ->whereBetween('borrow_date', [$start->toDateString(), $end->toDateString()])
                    ->groupBy('user_id')
                    ->orderByDesc('total_requests')
                    ->get()
                    ->map(function ($row) {
                        $user = User::find($row->user_id);
                        return [
                            $row->user_id,
                            $user?->getFullNameAttribute() ?? $row->user_id,
                            (int)$row->total_requests,
                            (int)$row->total_manpower,
                        ];
                    })->toArray();

                $columns = ['User ID','Name','Requests','Manpower Sum'];
                $rows = $users;

                return compact('columns','rows');

            case 'category_summary':
                $rowsRaw = DB::table('borrow_request_items')
                    ->join('borrow_requests','borrow_request_items.borrow_request_id','borrow_requests.id')
                    ->join('items','borrow_request_items.item_id','items.id')
                    ->select('items.category', DB::raw('SUM(borrow_request_items.quantity) as total'))
                    ->whereBetween('borrow_requests.borrow_date', [$start->toDateString(), $end->toDateString()])
                    ->whereIn('borrow_requests.status', ['approved','returned','pending'])
                    ->groupBy('items.category')
                    ->get();

                $columns = ['Category','Total Borrowed'];
                $rows = $rowsRaw->map(fn($r)=>[$r->category,(int)$r->total])->toArray();
                return compact('columns','rows');

            case 'manpower_usage':
                $sum = BorrowRequest::whereBetween('borrow_date', [$start->toDateString(), $end->toDateString()])
                    ->sum('manpower_count');

                $columns = ['Metric','Value'];
                $rows = [['Total Manpower Used', (int)$sum]];
                return compact('columns','rows');

            default:
                return ['columns' => [], 'rows' => []];
        }
    }
}
