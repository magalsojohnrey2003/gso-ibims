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
    'borrowed_items' => 'Borrowed Items',
    'returned_items' => 'Returned Items',
    'inventory_summary' => 'Inventory Summary',
    'borrower_condition_summary' => 'Borrower Condition Summary',
    'top_borrowed' => 'Top Borrowed Items',
    'low_stock' => 'Low Stock Items',
    'recent_borrows' => 'Recent Borrows',
    'damage_reports' => 'Damage Reports',
    'manpower_requests' => 'Manpower Requests',
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

            // Load the view into a PDF
            $pdf = Pdf::loadView('admin.reports.pdf', [
                'columns' => $columns,
                'rows'    => $rows,
                'meta'    => $meta,
            ])->setPaper('a4', 'portrait'); // or 'landscape' if you prefer

            /*
            * Add page numbers using Dompdf canvas.
            * The placeholders {PAGE_NUM} and {PAGE_COUNT} work with page_text().
            * We'll choose a font available to Dompdf (DejaVu Sans used in blade).
            */
           // --- Page numbering: centered "1 / 2" style (small, light gray) ---
           // --- Page numbering: centered "1 / 2" style (small, light gray) ---
            $dompdf = $pdf->getDomPDF();
            $canvas = $dompdf->get_canvas();

            // Use the same font family as the document (DejaVu Sans included)
            $font = $dompdf->getFontMetrics()->get_font("DejaVu Sans", "normal");

            // Desired display text: "1 / 2" (Dompdf will replace placeholders)
            $text = "{PAGE_NUM} / {PAGE_COUNT}";

            // Choose font size (points)
            $size = 9;

            // Compute approximate text width so we can center precisely.
            // Note: width is measured for the placeholder string; works well for centering.
            $textWidth = $dompdf->getFontMetrics()->getTextWidth($text, $font, $size);

            // Canvas dimensions (points)
            $canvasWidth = $canvas->get_width();
            $canvasHeight = $canvas->get_height();

            // Center horizontally, place a bit above bottom margin
            $x = ($canvasWidth - $textWidth) / 2;
            $y = $canvasHeight - 26; // tweak ±2-4 if you want it higher/lower

            // Light gray color (RGB as floats 0..1)
            $color = [0.45, 0.45, 0.45];

            // Draw page text on every page, centered
            $canvas->page_text($x, $y, $text, $font, $size, $color);



            // Finally return the download
            return $pdf->download($this->fileName($meta, 'pdf'));

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
            /**
             * 1) Borrowed Items
             * Columns: Borrower Name | Item Name | Request Date | Return Date | Status | Quantity
             */
            case 'borrowed_items':
                $rows = \DB::table('borrow_request_items as bri')
                    ->join('borrow_requests as br', 'bri.borrow_request_id', '=', 'br.id')
                    ->leftJoin('users as u', 'br.user_id', '=', 'u.id')
                    ->leftJoin('items as i', 'bri.item_id', '=', 'i.id')
                    ->whereBetween('br.borrow_date', [$start->toDateString(), $end->toDateString()])
                    ->select(
                        \DB::raw("CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as borrower_name"),
                        'i.name as item_name',
                        'br.borrow_date as request_date',
                        'br.return_date as return_date',
                        'br.status as status',
                        'bri.quantity as quantity'
                    )
                    ->orderBy('br.borrow_date', 'desc')
                    ->get()
                    ->map(fn($r) => [
                        $r->borrower_name,
                        $r->item_name,
                        $r->request_date,
                        $r->return_date,
                        ucfirst($r->status ?? ''),
                        (int)$r->quantity,
                    ])->toArray();

                $columns = ['Borrower Name','Item Name','Request Date','Return Date','Status','Quantity'];
                return compact('columns','rows');

            /**
             * 2) Returned Items
             * Columns: Borrower Name | Item Name | Condition | Status | Quantity | Return Date
             */
            case 'returned_items':
                $rows = \DB::table('return_items as ri')
                    ->join('return_requests as rr', 'ri.return_request_id', '=', 'rr.id')
                    ->leftJoin('borrow_requests as br', 'ri.borrow_request_id', '=', 'br.id')
                    ->leftJoin('users as u', 'rr.user_id', '=', 'u.id')
                    ->leftJoin('items as i', 'ri.item_id', '=', 'i.id')
                    ->whereBetween('rr.created_at', [$start->toDateString(), $end->toDateString()])
                    ->select(
                        \DB::raw("CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as borrower_name"),
                        'i.name as item_name',
                        'ri.condition as condition',
                        'rr.status as status',
                        'ri.quantity as quantity',
                        'rr.created_at as return_date'
                    )
                    ->orderBy('rr.created_at', 'desc')
                    ->get()
                    ->map(fn($r) => [
                        $r->borrower_name,
                        $r->item_name,
                        ucfirst(str_replace('_',' ',$r->condition ?? '')),
                        ucfirst($r->status ?? ''),
                        (int)$r->quantity,
                        $r->return_date ? (string)$r->return_date : null,
                    ])->toArray();

                $columns = ['Borrower Name','Item Name','Condition','Status','Quantity','Return Date'];
                return compact('columns','rows');

            /**
             * 3) Inventory Summary
             * Columns:
             * Item Name | Property Number | Condition | Total Quantity | Available Quantity | Total Requests
             *
             * We'll return one row per item, include a representative property number (first instance) and condition = N/A (because instance-level condition not tracked centrally)
             */
            case 'inventory_summary':
                $items = \App\Models\Item::with(['instances'])
                    ->orderBy('name')->get();

                $rows = $items->map(function($i) {
                    $prop = $i->instances->first()?->property_number ?? '-';
                    // condition is per-return/instance; default to 'N/A' here
                    $condition = 'N/A';
                    $totalRequests = \App\Models\BorrowRequestItem::where('item_id', $i->id)->sum('quantity');
                    return [
                        $i->name,
                        $prop,
                        $condition,
                        (int)$i->total_qty,
                        (int)$i->available_qty,
                        (int)$totalRequests,
                    ];
                })->toArray();

                $columns = ['Item Name','Property Number','Condition','Total Quantity','Available Quantity','Total Requests'];
                return compact('columns','rows');

            /**
             * 4) Borrower Condition Summary
             * Columns: Borrower Name | Good | Damage | Total Items Borrowed
             *
             * Counts conditions from return_items.condition - 'good' vs others mapped to damage.
             */
            case 'borrower_condition_summary':
                $sub = \DB::table('return_items as ri')
                    ->join('return_requests as rr', 'ri.return_request_id', '=', 'rr.id')
                    ->join('users as u', 'rr.user_id', '=', 'u.id')
                    ->select(
                        'rr.user_id',
                        \DB::raw("CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as borrower_name"),
                        'ri.condition',
                        \DB::raw('SUM(ri.quantity) as qty')
                    )
                    ->whereBetween('rr.created_at', [$start->toDateString(), $end->toDateString()])
                    ->groupBy('rr.user_id', 'borrower_name', 'ri.condition');

                $rowsRaw = \DB::table(\DB::raw("({$sub->toSql()}) as t"))
                    ->mergeBindings($sub)
                    ->select('borrower_name', 'condition', 'qty')
                    ->get()
                    ->groupBy('borrower_name');

                $out = [];
                foreach ($rowsRaw as $borrower => $group) {
                    $good = 0; $damage = 0; $total = 0;
                    foreach ($group as $r) {
                        $c = strtolower($r->condition ?? 'good');
                        $q = (int)$r->qty;
                        $total += $q;
                        if ($c === 'good') $good += $q;
                        else $damage += $q;
                    }
                    $out[] = [$borrower, $good, $damage, $total];
                }

                $columns = ['Borrower Name','Good','Damage','Total Items Borrowed'];
                $rows = $out;
                return compact('columns','rows');

            /**
             * 5) Top Borrowed Items
             * Columns: Item Name | Property Number | Borrow Frequency | Last Borrowed Date
             */
            case 'top_borrowed':
                $items = \DB::table('borrow_request_items as bri')
                    ->join('borrow_requests as br', 'bri.borrow_request_id', '=', 'br.id')
                    ->join('items as i', 'bri.item_id', '=', 'i.id')
                    ->whereBetween('br.borrow_date', [$start->toDateString(), $end->toDateString()])
                    ->select('bri.item_id', 'i.name as item_name', \DB::raw('SUM(bri.quantity) as freq'), \DB::raw('MAX(br.borrow_date) as last_borrowed'))
                    ->groupBy('bri.item_id', 'i.name')
                    ->orderByDesc('freq')
                    ->get();

                $rows = $items->map(function($r) {
                    // pick a representative property number (first instance) if available
                    $prop = \App\Models\ItemInstance::where('item_id', $r->item_id)->value('property_number') ?? '-';
                    return [$r->item_name, $prop, (int)$r->freq, (string)$r->last_borrowed];
                })->toArray();

                $columns = ['Item Name','Property Number','Borrow Frequency','Last Borrowed Date'];
                return compact('columns','rows');

            /**
             * 6) Low Stock Items
             * Columns: Item Name | Property Number | Total Quantity | Available Quantity | Low Stock Threshold | Available Stock
             */
            case 'low_stock':
                $threshold = isset($opts['threshold']) ? (int)$opts['threshold'] : (isset($opts['threshold']) ? (int)$opts['threshold'] : 5);
                $q = \App\Models\Item::where('available_qty', '<=', $threshold)
                    ->orderBy('available_qty','asc')
                    ->get();

                $rows = $q->map(function($i) use ($threshold) {
                    $prop = $i->instances()->value('property_number') ?? '-';
                    return [
                        $i->name,
                        $prop,
                        (int)$i->total_qty,
                        (int)$i->available_qty,
                        $threshold,
                        (int)$i->available_qty,
                    ];
                })->toArray();

                $columns = ['Item Name','Property Number','Total Quantity','Available Quantity','Low Stock Threshold','Available Stock'];
                $extra = ['kpis' => [['label' => 'Threshold', 'value' => $threshold], ['label' => 'Low stock count', 'value' => count($rows)]]];
                return compact('columns','rows','extra');

            /**
             * 7) Recent Borrows
             * Columns: Borrower Name | Item Name | Request Date | Return Date
             */
            case 'recent_borrows':
                $rows = \DB::table('borrow_request_items as bri')
                    ->join('borrow_requests as br', 'bri.borrow_request_id', '=', 'br.id')
                    ->leftJoin('users as u', 'br.user_id', '=', 'u.id')
                    ->leftJoin('items as i', 'bri.item_id', '=', 'i.id')
                    ->whereBetween('br.borrow_date', [$start->toDateString(), $end->toDateString()])
                    ->select(
                        \DB::raw("CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as borrower_name"),
                        'i.name as item_name',
                        'br.borrow_date as request_date',
                        'br.return_date as return_date'
                    )
                    ->orderBy('br.borrow_date', 'desc')
                    ->get()
                    ->map(fn($r) => [$r->borrower_name, $r->item_name, $r->request_date, $r->return_date])
                    ->toArray();

                $columns = ['Borrower Name','Item Name','Request Date','Return Date'];
                return compact('columns','rows');

            /**
             * 8) Damage Reports
             * Columns: Borrower Name | Item Name | Property Number | Damage Type | Reported Date
             * Uses item_damage_reports if present; otherwise falls back to return_items with damaged conditions
             */
            case 'damage_reports':
                // Prefer ItemDamageReport model if available
                if (class_exists(\App\Models\ItemDamageReport::class)) {
                    $reports = \App\Models\ItemDamageReport::with(['itemInstance.item','reporter'])
                        ->whereBetween('created_at', [$start->toDateString(), $end->toDateString()])
                        ->get();

                    $rows = $reports->map(function($rep) {
                        $borrower = $rep->reporter ? trim(($rep->reporter->first_name ?? '') . ' ' . ($rep->reporter->last_name ?? '')) : null;
                        $itemName = $rep->itemInstance?->item?->name ?? null;
                        $prop = $rep->itemInstance?->property_number ?? null;
                        $type = $rep->status ?? 'reported';
                        return [$borrower, $itemName, $prop, ucfirst(str_replace('_',' ',$type)), $rep->created_at?->toDateString()];
                    })->toArray();
                } else {
                    // Fallback: use return_items where condition indicates damage
                    $rows = \DB::table('return_items as ri')
                        ->join('return_requests as rr', 'ri.return_request_id', '=', 'rr.id')
                        ->leftJoin('items as i', 'ri.item_id', '=', 'i.id')
                        ->leftJoin('item_instances as ii', 'ri.item_instance_id', '=', 'ii.id')
                        ->leftJoin('users as u', 'rr.user_id', '=', 'u.id')
                        ->whereBetween('rr.created_at', [$start->toDateString(), $end->toDateString()])
                        ->whereIn('ri.condition', ['major_damage','minor_damage','damaged','missing'])
                        ->select(
                            \DB::raw("CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as borrower_name"),
                            'i.name as item_name',
                            'ii.property_number as property_number',
                            'ri.condition as damage_type',
                            'rr.created_at as reported_date'
                        )
                        ->orderBy('rr.created_at', 'desc')
                        ->get()
                        ->map(fn($r) => [
                            $r->borrower_name,
                            $r->item_name,
                            $r->property_number,
                            ucfirst(str_replace('_',' ',$r->damage_type)),
                            (string)$r->reported_date
                        ])->toArray();
                }

                $columns = ['Borrower Name','Item Name','Property Number','Damage Type','Reported Date'];
                return compact('columns','rows');

            /**
             * 9) Manpower Requests
             * Columns: Request ID | Role | Manpower Quantity | Item Requested | Operational Location | Request Date | Status
             *
             * Best-effort: combine borrow_requests + borrow_request_items role/assigned_manpower
             */
            case 'manpower_requests':
                // find borrow_request_items that have manpower info OR borrow_requests with manpower_count > 0
                $items = \DB::table('borrow_request_items as bri')
                    ->join('borrow_requests as br', 'bri.borrow_request_id', '=', 'br.id')
                    ->leftJoin('items as i', 'bri.item_id', '=', 'i.id')
                    ->select(
                        'br.id as request_id',
                        'bri.manpower_role as role',
                        \DB::raw('COALESCE(bri.assigned_manpower, br.manpower_count, 0) as manpower_qty'),
                        'i.name as item_requested',
                        'br.borrow_date as request_date',
                        'br.status as status'
                    )
                    ->whereBetween('br.borrow_date', [$start->toDateString(), $end->toDateString()])
                    ->orderBy('br.borrow_date', 'desc')
                    ->get();

                // Also include requests where no specific bri row exists but borrow_requests.manpower_count > 0
                $extraRequests = \DB::table('borrow_requests as br')
                    ->leftJoin('borrow_request_items as bri', 'br.id', '=', 'bri.borrow_request_id')
                    ->whereNull('bri.id')
                    ->where('br.manpower_count', '>', 0)
                    ->whereBetween('br.borrow_date', [$start->toDateString(), $end->toDateString()])
                    ->select(
                        'br.id as request_id',
                        \DB::raw("NULL as role"),
                        \DB::raw('br.manpower_count as manpower_qty'),
                        \DB::raw("NULL as item_requested"),
                        'br.borrow_date as request_date',
                        'br.status as status'
                    )
                    ->get();

                $all = $items->concat($extraRequests);

                $rows = $all->map(function($r) {
                    return [
                        str_pad((string)$r->request_id, 3, '0', STR_PAD_LEFT),
                        $r->role ? $r->role : '-',
                        (int)$r->manpower_qty,
                        $r->item_requested ?? '-',
                        '-', // Operational location not present in schema by default
                        $r->request_date ? (string)$r->request_date : null,
                        ucfirst($r->status ?? ''),
                    ];
                })->toArray();

                $columns = ['Request ID','Role','Manpower Quantity','Item Requested','Operational Location','Request Date','Status'];
                return compact('columns','rows');

            default:
                return ['columns' => [], 'rows' => []];
        }
    }

}
