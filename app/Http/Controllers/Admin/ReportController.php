<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\BorrowItemInstance;
use App\Models\ItemInstance;
use App\Models\ManpowerRequest;
use App\Models\ItemDamageReport;
use App\Models\Category;
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
        'borrowed_items'             => 'Borrowed Items',
        'returned_items'             => 'Returned Items',
        'inventory_summary'          => 'Inventory Summary',
        'borrower_condition_summary' => 'Borrower Condition Summary',
        'top_borrowed'               => 'Top Borrowed Items',
        'low_stock'                  => 'Low Stock Items',
        'recent_borrows'             => 'Recent Borrows',
        'missing_reports'            => 'Missing Reports',
        'damage_reports'             => 'Damage Reports',
        'manpower_requests'          => 'Manpower Requests',
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
            case 'borrowed_items':
                $now = Carbon::now()->startOfDay();
                $records = DB::table('borrow_item_instances as bii')
                    ->join('items as i', 'bii.item_id', '=', 'i.id')
                    ->leftJoin('item_instances as inst', 'bii.item_instance_id', '=', 'inst.id')
                    ->leftJoin('borrow_requests as br', 'bii.borrow_request_id', '=', 'br.id')
                    ->leftJoin('users as u', 'br.user_id', '=', 'u.id')
                    ->whereNull('bii.returned_at')
                    ->whereBetween(DB::raw("DATE(COALESCE(bii.checked_out_at, bii.created_at))"), [$start->toDateString(), $end->toDateString()])
                    ->select(
                        'i.name as item_name',
                        'inst.property_number as property_number',
                        DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) as borrower_name"),
                        'br.purpose_office as office_agency',
                        DB::raw("COALESCE(bii.checked_out_at, bii.created_at) as borrowed_at"),
                        'br.return_date as due_date'
                    )
                    ->orderByDesc(DB::raw("COALESCE(bii.checked_out_at, bii.created_at)"))
                    ->get();

                $rows = $records->map(function ($row) use ($now) {
                    $borrowedDate = $row->borrowed_at ? Carbon::parse($row->borrowed_at)->toDateString() : null;
                    $daysOverdue = 0;
                    $dueDate = null;
                    if ($row->due_date) {
                        $dueCarbon = Carbon::parse($row->due_date)->endOfDay();
                        $dueDate = $dueCarbon->toDateString();
                        if ($dueCarbon->lt($now)) {
                            $daysOverdue = $dueCarbon->diffInDays($now);
                        }
                    }

                    return [
                        $row->item_name,
                        $row->property_number ?? '—',
                        $row->borrower_name ?: '—',
                        $row->office_agency ?: '—',
                        $borrowedDate,
                        $dueDate,
                        $daysOverdue,
                    ];
                })->toArray();

                $overdueCount = collect($rows)->filter(fn ($row) => ($row[6] ?? 0) > 0)->count();

                $columns = ['Item Name', 'Property Number', 'Borrower Name', 'Office/Agency', 'Date Borrowed', 'Date Due', 'Days Overdue'];
                $extra = ['kpis' => [
                    ['label' => 'Active Loans', 'value' => count($rows)],
                    ['label' => 'Overdue Items', 'value' => $overdueCount],
                ]];
                return compact('columns', 'rows', 'extra');

            case 'returned_items':
                $records = DB::table('borrow_item_instances as bii')
                    ->join('items as i', 'bii.item_id', '=', 'i.id')
                    ->leftJoin('item_instances as inst', 'bii.item_instance_id', '=', 'inst.id')
                    ->leftJoin('borrow_requests as br', 'bii.borrow_request_id', '=', 'br.id')
                    ->leftJoin('users as u', 'br.user_id', '=', 'u.id')
                    ->whereNotNull('bii.returned_at')
                    ->whereBetween(DB::raw("DATE(bii.returned_at)"), [$start->toDateString(), $end->toDateString()])
                    ->select(
                        'i.name as item_name',
                        'inst.property_number as property_number',
                        DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) as borrower_name"),
                        DB::raw("COALESCE(bii.checked_out_at, bii.created_at) as borrowed_at"),
                        'bii.returned_at as returned_at',
                        'bii.return_condition as condition'
                    )
                    ->orderByDesc('bii.returned_at')
                    ->get();

                $rows = $records->map(function ($row) {
                    return [
                        $row->item_name,
                        $row->property_number ?? '—',
                        $row->borrower_name ?: '—',
                        $row->borrowed_at ? Carbon::parse($row->borrowed_at)->toDateString() : null,
                        $row->returned_at ? Carbon::parse($row->returned_at)->toDateString() : null,
                        $this->formatConditionLabel($row->condition),
                    ];
                })->toArray();

                $damagedCount = $records->filter(fn ($row) => in_array(strtolower($row->condition ?? ''), ['damage', 'minor_damage', 'missing'], true))->count();

                $columns = ['Item Name', 'Property Number', 'Borrower Name', 'Date Borrowed', 'Date Returned', 'Return Condition'];
                $extra = ['kpis' => [
                    ['label' => 'Returned Items', 'value' => count($rows)],
                    ['label' => 'Damaged / Lost', 'value' => $damagedCount],
                ]];
                return compact('columns', 'rows', 'extra');

            case 'inventory_summary':
                $items = Item::with('instances')->orderBy('name')->get();
                $rows = $items->map(function (Item $item) {
                    $total = (int) $item->total_qty;
                    $available = max(0, (int) $item->available_qty);
                    $inUse = max(0, $total - $available);

                    return [
                        $item->name,
                        $item->category_name ?? 'Uncategorized',
                        $total,
                        $available,
                        $inUse,
                    ];
                })->toArray();

                $columns = ['Item Name', 'Category', 'Total Quantity', 'Available Quantity', 'Quantity In Use'];
                $extra = ['kpis' => [
                    ['label' => 'Total Units', 'value' => $items->sum('total_qty')],
                    ['label' => 'Available Units', 'value' => $items->sum('available_qty')],
                ]];
                return compact('columns', 'rows', 'extra');

            case 'borrower_condition_summary':
                $records = DB::table('borrow_item_instances as bii')
                    ->join('items as i', 'bii.item_id', '=', 'i.id')
                    ->leftJoin('item_instances as inst', 'bii.item_instance_id', '=', 'inst.id')
                    ->leftJoin('borrow_requests as br', 'bii.borrow_request_id', '=', 'br.id')
                    ->leftJoin('users as u', 'br.user_id', '=', 'u.id')
                    ->whereIn('bii.return_condition', ['damage', 'minor_damage', 'missing'])
                    ->whereBetween(
                        DB::raw("DATE(COALESCE(bii.condition_updated_at, bii.returned_at, bii.updated_at))"),
                        [$start->toDateString(), $end->toDateString()]
                    )
                    ->select(
                        DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) as borrower_name"),
                        'br.purpose_office as office_agency',
                        'i.name as item_name',
                        'inst.property_number as property_number',
                        DB::raw("COALESCE(bii.returned_at, bii.condition_updated_at) as returned_at"),
                        'bii.return_condition as condition',
                        'inst.notes as return_notes'
                    )
                    ->orderByDesc(DB::raw("COALESCE(bii.condition_updated_at, bii.returned_at, bii.updated_at)"))
                    ->get();

                $rows = $records->map(function ($row) {
                    return [
                        $row->borrower_name ?: '—',
                        $row->office_agency ?: '—',
                        $row->item_name,
                        $row->property_number ?? '—',
                        $row->returned_at ? Carbon::parse($row->returned_at)->toDateString() : null,
                        $this->mapDamageOrLoss($row->condition),
                        $row->return_notes ?: '—',
                    ];
                })->toArray();

                $columns = ['Borrower Name', 'Office/Agency', 'Item Name', 'Property Number', 'Date Returned', 'Reported Condition', 'Return Notes'];
                $extra = ['kpis' => [
                    ['label' => 'Borrowers Reported', 'value' => $records->pluck('borrower_name')->filter()->unique()->count()],
                    ['label' => 'Damaged / Lost Items', 'value' => count($rows)],
                ]];
                return compact('columns', 'rows', 'extra');

            case 'top_borrowed':
                $records = DB::table('borrow_item_instances as bii')
                    ->join('items as i', 'bii.item_id', '=', 'i.id')
                    ->whereBetween(DB::raw("DATE(COALESCE(bii.checked_out_at, bii.created_at))"), [$start->toDateString(), $end->toDateString()])
                    ->select('i.id as item_id', 'i.name as item_name', 'i.category as category_value', DB::raw('COUNT(*) as borrow_count'))
                    ->groupBy('i.id', 'i.name', 'i.category')
                    ->orderByDesc('borrow_count')
                    ->get();

                $rows = $records->map(function ($row) {
                    return [
                        $row->item_name,
                        $this->resolveCategoryLabel($row->category_value),
                        (int) $row->borrow_count,
                    ];
                })->toArray();

                $columns = ['Item Name', 'Category', 'Total Borrows'];
                $extra = ['kpis' => [
                    ['label' => 'Distinct Items', 'value' => $records->count()],
                    ['label' => 'Total Borrows', 'value' => (int) $records->sum('borrow_count')],
                ]];
                return compact('columns', 'rows', 'extra');

            case 'low_stock':
                $threshold = isset($opts['threshold']) && $opts['threshold'] !== '' ? (int) $opts['threshold'] : 5;
                $items = Item::orderBy('available_qty')
                    ->get()
                    ->filter(fn (Item $item) => $item->available_qty <= $threshold);

                $rows = $items->map(function (Item $item) {
                    $status = $item->available_qty <= 0 ? 'Out of Stock' : 'Low Stock';
                    return [
                        $item->name,
                        $item->category_name ?? 'Uncategorized',
                        (int) $item->available_qty,
                        (int) $item->total_qty,
                        $status,
                    ];
                })->values()->toArray();

                $columns = ['Item Name', 'Category', 'Available Quantity', 'Total Quantity', 'Status'];
                $extra = ['kpis' => [
                    ['label' => 'Threshold', 'value' => $threshold],
                    ['label' => 'Flagged Items', 'value' => count($rows)],
                ]];
                return compact('columns', 'rows', 'extra');

            case 'recent_borrows':
                $records = DB::table('borrow_item_instances as bii')
                    ->join('items as i', 'bii.item_id', '=', 'i.id')
                    ->leftJoin('item_instances as inst', 'bii.item_instance_id', '=', 'inst.id')
                    ->leftJoin('borrow_requests as br', 'bii.borrow_request_id', '=', 'br.id')
                    ->leftJoin('users as u', 'br.user_id', '=', 'u.id')
                    ->whereBetween(DB::raw("DATE(COALESCE(bii.checked_out_at, bii.created_at))"), [$start->toDateString(), $end->toDateString()])
                    ->select(
                        DB::raw("DATE(COALESCE(bii.checked_out_at, bii.created_at)) as borrowed_date"),
                        'i.name as item_name',
                        'inst.property_number as property_number',
                        DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) as borrower_name"),
                        DB::raw("CASE WHEN bii.returned_at IS NULL THEN 'Borrowed' ELSE 'Returned' END as status_label")
                    )
                    ->orderByDesc(DB::raw("COALESCE(bii.checked_out_at, bii.created_at)"))
                    ->get();

                $rows = $records->map(function ($row) {
                    return [
                        $row->borrowed_date,
                        $row->item_name,
                        $row->property_number ?? 'N/A',
                        $row->borrower_name ?: 'N/A',
                        $row->status_label,
                    ];
                })->toArray();

                $returnedCount = $records->filter(fn ($row) => $row->status_label === 'Returned')->count();

                $columns = ['Date Borrowed', 'Item Name', 'Property Number', 'Borrower Name', 'Status'];
                $extra = ['kpis' => [
                    ['label' => 'Borrows Logged', 'value' => count($rows)],
                    ['label' => 'Returned', 'value' => $returnedCount],
                ]];
                return compact('columns', 'rows', 'extra');

            case 'missing_reports':
                $instances = ItemInstance::with([
                    'item',
                    'borrowRecords' => function ($query) {
                        $query->orderByDesc(DB::raw("COALESCE(checked_out_at, created_at)"));
                    },
                    'borrowRecords.borrowRequest.user',
                    'borrowRecords.walkInRequest',
                ])
                    ->where('status', 'missing')
                    ->get();

                $rows = $instances->map(function (ItemInstance $instance) {
                    /** @var BorrowItemInstance|null $lastRecord */
                    $lastRecord = $instance->borrowRecords->first();
                    $borrower = 'N/A';
                    if ($lastRecord) {
                        $user = $lastRecord->borrowRequest?->user;
                        if ($user) {
                            $borrower = $this->formatFullName($user->first_name ?? null, $user->last_name ?? null);
                        } elseif ($lastRecord->walkInRequest) {
                            $borrower = $lastRecord->walkInRequest->borrower_name ?? 'Walk-in Borrower';
                        }
                    }

                    return [
                        $instance->item?->name ?? 'Unknown Item',
                        $instance->property_number ?? 'N/A',
                        $instance->serial_no ?? $instance->serial ?? 'N/A',
                        $borrower ?: 'N/A',
                        $instance->updated_at?->toDateString(),
                    ];
                })->toArray();

                $columns = ['Item Name', 'Property Number', 'Serial Number', 'Last Borrower', 'Date Reported Missing'];
                $extra = ['kpis' => [
                    ['label' => 'Missing Items', 'value' => count($rows)],
                ]];
                return compact('columns', 'rows', 'extra');

            case 'damage_reports':
                if (class_exists(ItemDamageReport::class)) {
                    $reports = ItemDamageReport::with(['itemInstance.item', 'reporter'])
                        ->whereBetween(DB::raw("DATE(created_at)"), [$start->toDateString(), $end->toDateString()])
                        ->orderByDesc('created_at')
                        ->get();

                    $rows = $reports->map(function (ItemDamageReport $report) {
                        $reporter = $report->reporter
                            ? $this->formatFullName($report->reporter->first_name ?? null, $report->reporter->last_name ?? null)
                            : 'N/A';

                        return [
                            $report->itemInstance?->item?->name ?? 'Unknown Item',
                            $report->itemInstance?->property_number ?? 'N/A',
                            $reporter,
                            $report->created_at?->toDateTimeString(),
                            $report->description ?? '',
                            ucfirst(str_replace('_', ' ', $report->status ?? 'reported')),
                        ];
                    })->toArray();
                } else {
                    $fallback = DB::table('borrow_item_instances as bii')
                        ->join('items as i', 'bii.item_id', '=', 'i.id')
                        ->leftJoin('item_instances as inst', 'bii.item_instance_id', '=', 'inst.id')
                        ->leftJoin('borrow_requests as br', 'bii.borrow_request_id', '=', 'br.id')
                        ->leftJoin('users as u', 'br.user_id', '=', 'u.id')
                        ->whereIn('bii.return_condition', ['damage', 'minor_damage', 'missing'])
                        ->whereBetween(
                            DB::raw("DATE(COALESCE(bii.condition_updated_at, bii.returned_at, bii.updated_at))"),
                            [$start->toDateString(), $end->toDateString()]
                        )
                        ->select(
                            'i.name as item_name',
                            'inst.property_number as property_number',
                            DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) as reporter_name"),
                            DB::raw("COALESCE(bii.condition_updated_at, bii.returned_at, bii.updated_at) as reported_at"),
                            'bii.return_condition as condition'
                        )
                        ->orderByDesc(DB::raw("COALESCE(bii.condition_updated_at, bii.returned_at, bii.updated_at)"))
                        ->get();

                    $rows = $fallback->map(function ($row) {
                        $status = $this->mapDamageOrLoss($row->condition);
                        return [
                            $row->item_name,
                            $row->property_number ?? 'N/A',
                            $row->reporter_name ?: 'N/A',
                            $row->reported_at ? Carbon::parse($row->reported_at)->toDateTimeString() : null,
                            'Condition recorded as ' . $status,
                            $status,
                        ];
                    })->toArray();
                }

                $columns = ['Item Name', 'Property Number', 'Reported By', 'Date Reported', 'Description of Damage', 'Status'];
                $extra = ['kpis' => [
                    ['label' => 'Reports Logged', 'value' => count($rows)],
                ]];
                return compact('columns', 'rows', 'extra');

            case 'manpower_requests':
                $requests = ManpowerRequest::with('user')
                    ->whereBetween(DB::raw("DATE(COALESCE(start_at, created_at))"), [$start->toDateString(), $end->toDateString()])
                    ->orderByDesc(DB::raw("COALESCE(start_at, created_at)"))
                    ->get();

                $rows = $requests->map(function (ManpowerRequest $request) {
                    $user = $request->user;
                    $requester = $user?->full_name ?? $this->formatFullName($user->first_name ?? null, $user->last_name ?? null);

                    return [
                        $requester ?: 'N/A',
                        $request->office_agency ?: 'N/A',
                        $request->purpose ?? 'N/A',
                        (int) $request->quantity,
                        $request->start_at?->toDateString() ?? $request->created_at?->toDateString(),
                        $request->end_at?->toDateString(),
                        ucfirst($request->status ?? 'pending'),
                    ];
                })->toArray();

                $statusBreakdown = $requests->groupBy(fn ($req) => strtolower($req->status ?? 'pending'))->map->count();

                $columns = ['Requester Name', 'Office/Agency', 'Purpose of Request', 'Quantity Requested', 'Start Date', 'End Date', 'Status'];
                $extra = ['kpis' => [
                    ['label' => 'Total Requests', 'value' => count($rows)],
                    ['label' => 'Approved', 'value' => $statusBreakdown['approved'] ?? 0],
                ]];
                return compact('columns', 'rows', 'extra');

            default:
                return ['columns' => [], 'rows' => []];
        }
    }

    protected function resolveCategoryLabel($value): string
    {
        static $cache = [];

        if ($value === null || $value === '') {
            return 'Uncategorized';
        }

        if (is_numeric($value)) {
            $key = (int) $value;
            if (! array_key_exists($key, $cache)) {
                $cache[$key] = Category::find($key)?->name ?? 'Uncategorized';
            }
            return $cache[$key];
        }

        return (string) $value;
    }

    protected function formatFullName(?string $first, ?string $last): string
    {
        $name = trim(($first ?? '') . ' ' . ($last ?? ''));
        return $name !== '' ? $name : 'N/A';
    }

    protected function formatConditionLabel(?string $condition): string
    {
        return match (strtolower($condition ?? '')) {
            'good' => 'Good',
            'damage' => 'Damaged',
            'damaged' => 'Damaged',
            'minor_damage' => 'Minor Damage',
            'missing' => 'Lost',
            default => 'Pending',
        };
    }

    protected function mapDamageOrLoss(?string $condition): string
    {
        return strtolower($condition ?? '') === 'missing' ? 'Lost' : 'Damaged';
    }
}
