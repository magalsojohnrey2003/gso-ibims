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
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use DateTimeInterface;
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
            'start' => $this->formatDisplayDate($start),
            'end' => $this->formatDisplayDate($end),
            'start_iso' => $start->toDateString(),
            'end_iso' => $end->toDateString(),
            'generated_at' => $this->formatDisplayDate(Carbon::now(), true),
        ];
    }

    protected function fileName(array $meta, string $ext): string
    {
        $base = str_replace(' ', '_', strtolower($meta['title'] ?? 'report'));
        $startSlug = $meta['start_iso'] ?? $meta['start'] ?? Carbon::now()->toDateString();
        $endSlug = $meta['end_iso'] ?? $meta['end'] ?? Carbon::now()->toDateString();

        $startSlug = preg_replace('/[^0-9\-]/', '', (string) $startSlug) ?: Carbon::now()->toDateString();
        $endSlug = preg_replace('/[^0-9\-]/', '', (string) $endSlug) ?: Carbon::now()->toDateString();

        return "{$base}_{$startSlug}_to_{$endSlug}.{$ext}";
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

                $query = DB::table('borrow_item_instances as bii')
                    ->join('items as i', 'bii.item_id', '=', 'i.id')
                    ->leftJoin('item_instances as inst', 'bii.item_instance_id', '=', 'inst.id')
                    ->leftJoin('borrow_requests as br', 'bii.borrow_request_id', '=', 'br.id')
                    ->leftJoin('users as u', 'br.user_id', '=', 'u.id')
                    ->leftJoin('walk_in_requests as wir', 'bii.walk_in_request_id', '=', 'wir.id')
                    ->whereNull('bii.returned_at')
                    ->whereBetween(DB::raw("DATE(COALESCE(bii.checked_out_at, bii.created_at))"), [$start->toDateString(), $end->toDateString()]);

                $selects = [
                    'i.name as item_name',
                    'inst.property_number as property_number',
                    DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) as borrower_name"),
                    'u.phone as borrower_phone',
                    DB::raw("COALESCE(bii.checked_out_at, bii.created_at) as borrowed_at"),
                    'br.return_date as due_date',
                    'br.purpose_office as purpose_office',
                    'wir.borrower_name as walk_in_borrower_name',
                    'wir.contact_number as walk_in_contact',
                    'wir.office_agency as walk_in_office',
                    'wir.borrowed_at as walk_in_borrowed_at',
                    'wir.returned_at as walk_in_due_at',
                ];

                if (Schema::hasColumn('users', 'phone_number')) {
                    $selects[] = 'u.phone_number as borrower_phone_number';
                } else {
                    $selects[] = DB::raw('NULL as borrower_phone_number');
                }

                if (Schema::hasColumn('users', 'office_id')) {
                    $query->leftJoin('offices as office', 'u.office_id', '=', 'office.id');
                    $selects[] = 'office.name as user_office_name';
                } else {
                    $selects[] = DB::raw('NULL as user_office_name');
                }

                $records = $query->select($selects)
                    ->orderByDesc(DB::raw("COALESCE(bii.checked_out_at, bii.created_at)"))
                    ->get();

                $overdueCount = 0;
                $rows = [];

                foreach ($records as $row) {
                    $borrower = $row->borrower_name ?: $row->walk_in_borrower_name ?: '—';

                    $contact = $row->borrower_phone_number ?: $row->borrower_phone;
                    if (! $contact) {
                        $contact = $row->walk_in_contact ?: '—';
                    }

                    $office = $row->user_office_name ?: $row->purpose_office ?: $row->walk_in_office ?: '—';

                    $borrowedSource = $row->borrowed_at ?: $row->walk_in_borrowed_at;
                    $borrowedDate = $this->formatDateCell($borrowedSource);

                    $dueSource = $row->due_date ?: $row->walk_in_due_at;
                    $dueDate = $this->formatDateCell($dueSource);

                    if ($dueSource) {
                        $dueCarbon = Carbon::parse($dueSource)->endOfDay();
                        if ($dueCarbon->lt($now)) {
                            $overdueCount++;
                        }
                    }

                    $rows[] = [
                        $row->item_name,
                        $row->property_number ?? '—',
                        $borrower,
                        $contact ?: '—',
                        $office ?: '—',
                        $borrowedDate,
                        $dueDate,
                    ];
                }

                $columns = ['Item Name', 'Property No.', 'Borrower', 'Contact #', 'Office/Agency', 'Date Borrowed', 'Due Date'];
                $extra = ['kpis' => [
                    ['label' => 'Active Loans', 'value' => count($rows)],
                    ['label' => 'Overdue Items', 'value' => $overdueCount],
                ]];
                return compact('columns', 'rows', 'extra');

            case 'returned_items':
                $query = DB::table('borrow_item_instances as bii')
                    ->join('items as i', 'bii.item_id', '=', 'i.id')
                    ->leftJoin('item_instances as inst', 'bii.item_instance_id', '=', 'inst.id')
                    ->leftJoin('borrow_requests as br', 'bii.borrow_request_id', '=', 'br.id')
                    ->leftJoin('users as u', 'br.user_id', '=', 'u.id')
                    ->leftJoin('walk_in_requests as wir', 'bii.walk_in_request_id', '=', 'wir.id')
                    ->whereNotNull('bii.returned_at')
                    ->whereBetween(DB::raw("DATE(bii.returned_at)"), [$start->toDateString(), $end->toDateString()]);

                $selects = [
                    'i.name as item_name',
                    'inst.property_number as property_number',
                    DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) as borrower_name"),
                    DB::raw("COALESCE(bii.checked_out_at, bii.created_at) as borrowed_at"),
                    'bii.returned_at as returned_at',
                    'bii.return_condition as condition',
                    'br.purpose_office as purpose_office',
                    'wir.borrower_name as walk_in_borrower_name',
                    'wir.office_agency as walk_in_office',
                    'wir.returned_at as walk_in_returned_at',
                ];

                if (Schema::hasColumn('users', 'office_id')) {
                    $query->leftJoin('offices as office', 'u.office_id', '=', 'office.id');
                    $selects[] = 'office.name as user_office_name';
                } else {
                    $selects[] = DB::raw('NULL as user_office_name');
                }

                $records = $query->select($selects)
                    ->orderByDesc('bii.returned_at')
                    ->get();

                $damagedCount = 0;
                $rows = [];

                foreach ($records as $row) {
                    $borrower = $row->borrower_name ?: $row->walk_in_borrower_name ?: '—';
                    $office = $row->user_office_name ?: $row->purpose_office ?: $row->walk_in_office ?: '—';

                    $returnedSource = $row->returned_at ?: $row->walk_in_returned_at;

                    $conditionLabel = $this->formatConditionLabel($row->condition);
                    if (in_array(strtolower($row->condition ?? ''), ['damage', 'minor_damage', 'missing'], true)) {
                        $damagedCount++;
                    }

                    $rows[] = [
                        $row->item_name,
                        $row->property_number ?? '—',
                        $borrower,
                        $office ?: '—',
                        $this->formatDateCell($returnedSource),
                        $conditionLabel,
                    ];
                }

                $columns = ['Item Name', 'Property Number', 'Borrower', 'Office/Agency', 'Date Returned', 'Condition'];
                $extra = ['kpis' => [
                    ['label' => 'Returned Items', 'value' => count($rows)],
                    ['label' => 'Damaged / Lost', 'value' => $damagedCount],
                ]];
                return compact('columns', 'rows', 'extra');

            case 'inventory_summary':
                $items = Item::with('instances')->orderBy('name')->get();
                $rows = $items->map(function (Item $item) {
                    $acquisitionDate = $this->formatDateCell($item->acquisition_date);
                    $unitValue = $item->acquisition_cost;

                    return [
                        $item->name,
                        $item->category_name ?? 'Uncategorized',
                        (int) $item->total_qty,
                        max(0, (int) $item->available_qty),
                        $acquisitionDate,
                        $unitValue !== null ? number_format((int) $unitValue, 2) : '—',
                    ];
                })->toArray();

                $columns = ['Item Name', 'Category', 'Total QTY', 'Available QTY', 'Acquisition Date', 'Unit Value'];
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
                        $this->formatDateCell($row->returned_at),
                        $this->mapDamageOrLoss($row->condition),
                    ];
                })->toArray();

                $columns = ['Borrower', 'Office/Agency', 'Item Name', 'Property Number', 'Date Returned', 'Condition'];
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

                $columns = ['Item Name', 'Category', 'Available QTY', 'Total QTY', 'Status'];
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
                        $this->formatDateCell($row->borrowed_date),
                        $row->item_name,
                        $row->property_number ?? 'N/A',
                        $row->borrower_name ?: 'N/A',
                        $row->status_label,
                    ];
                })->toArray();

                $returnedCount = $records->filter(fn ($row) => $row->status_label === 'Returned')->count();

                $columns = ['Date Borrowed', 'Item Name', 'Property No.', 'Borrower', 'Status'];
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
                        $this->formatDateCell($instance->updated_at),
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

                        $instance = $report->itemInstance;
                        $serial = $instance?->serial_no ?? $instance?->serial ?? 'N/A';

                        return [
                            $reporter,
                            $instance?->item?->name ?? 'Unknown Item',
                            $instance?->property_number ?? 'N/A',
                            $serial ?: 'N/A',
                            $this->formatDateCell($report->created_at, true),
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
                            'inst.serial_no as serial_no',
                            'inst.serial as serial_alt',
                            DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) as reporter_name"),
                            DB::raw("COALESCE(bii.condition_updated_at, bii.returned_at, bii.updated_at) as reported_at"),
                            'bii.return_condition as condition'
                        )
                        ->orderByDesc(DB::raw("COALESCE(bii.condition_updated_at, bii.returned_at, bii.updated_at)"))
                        ->get();

                    $rows = $fallback->map(function ($row) {
                        $status = $this->mapDamageOrLoss($row->condition);
                        $serial = $row->serial_no ?? $row->serial_alt ?? 'N/A';

                        return [
                            $row->reporter_name ?: 'N/A',
                            $row->item_name,
                            $row->property_number ?? 'N/A',
                            $serial ?: 'N/A',
                            $this->formatDateCell($row->reported_at, true),
                            $status,
                        ];
                    })->toArray();
                }

                $columns = ['Reported By', 'Item Name', 'Property Number', 'Serial No.', 'Date Reported', 'Status'];
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
                        $this->formatDateCell($request->start_at ?? $request->created_at),
                        $this->formatDateCell($request->end_at),
                        ucfirst($request->status ?? 'pending'),
                    ];
                })->toArray();

                $statusBreakdown = $requests->groupBy(fn ($req) => strtolower($req->status ?? 'pending'))->map->count();

                $columns = ['Requester Name', 'Office/Agency', 'Purpose of Request', 'Requested QTY', 'Start Date', 'End Date', 'Status'];
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

    protected function formatDateCell($value, bool $withTime = false): string
    {
        $formatted = $this->formatDisplayDate($value, $withTime);
        return $formatted ?? '—';
    }

    protected function formatDisplayDate($value, bool $withTime = false): ?string
    {
        if ($value === null || $value === '' || (is_string($value) && strtoupper(trim($value)) === 'N/A')) {
            return null;
        }

        if ($value instanceof Carbon) {
            $carbon = $value->copy();
        } elseif ($value instanceof DateTimeInterface) {
            $carbon = Carbon::instance($value);
        } else {
            try {
                $carbon = Carbon::parse($value);
            } catch (Throwable $e) {
                return null;
            }
        }

        $monthMap = [
            1 => 'Jan.',
            2 => 'Feb.',
            3 => 'Mar.',
            4 => 'Apr.',
            5 => 'May',
            6 => 'Jun.',
            7 => 'Jul.',
            8 => 'Aug.',
            9 => 'Sep.',
            10 => 'Oct.',
            11 => 'Nov.',
            12 => 'Dec.',
        ];

        $month = $monthMap[$carbon->month] ?? $carbon->format('M');
        $dateString = sprintf('%s %d, %s', $month, (int) $carbon->format('j'), $carbon->format('Y'));

        if ($withTime) {
            $dateString .= ' ' . $carbon->format('g:i A');
        }

        return $dateString;
    }
}
