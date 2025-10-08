{{-- resources/views/pdf/borrow-request.blade.php (modern layout) --}}
@php
    $r = $borrowRequest ?? null;
    $forPdf = $forPdf ?? false;

    $fmtDate = function ($d) {
        if (!$d) return '';
        if (is_object($d) && method_exists($d, 'format')) return $d->format('F j, Y');
        try { return \Carbon\Carbon::parse($d)->format('F j, Y'); } catch (\Throwable $e) { return (string) $d; }
    };

    // Logos
    $logoLeftFs  = public_path('images/logo.png');
    $logoRightFs = public_path('images/logo2.png');

    // Signature names (fallbacks)
    $verifiedByName  = 'APRIL ROSE V. APAS';
    $verifiedByTitle = 'GSO Staff';
    $notedByName     = 'ERIC P. RAGANDANG, CE';
    $notedByTitle    = 'OIC - General Services Officer';
    $approvedByName  = 'ATTY. NADYA B. EMANO-ELIPE';
    $approvedByTitle = 'Municipal Mayor';

    $items = $r->items ?? [];

    $row_item_id = function ($ri) {
        if (is_object($ri)) {
            if (isset($ri->item) && $ri->item) return optional($ri->item)->id ?? ($ri->item_id ?? null);
            return $ri->item_id ?? ($ri->id ?? null);
        }
        if (is_array($ri)) {
            return $ri['item']['id'] ?? ($ri['item_id'] ?? ($ri['id'] ?? null));
        }
        return null;
    };
    $row_item_name = function ($ri) {
        if (is_object($ri)) {
            if (isset($ri->item) && $ri->item) return optional($ri->item)->name ?? ($ri->name ?? null);
            return $ri->name ?? null;
        }
        if (is_array($ri)) return $ri['item']['name'] ?? ($ri['name'] ?? null);
        return null;
    };
    $row_qty = function ($ri) { return is_object($ri) ? ($ri->quantity ?? ($ri->qty ?? null)) : (is_array($ri) ? ($ri['quantity'] ?? ($ri['qty'] ?? null)) : null); };
    $row_assigned = function ($ri) { return (int) (is_object($ri) ? ($ri->assigned_manpower ?? 0) : (is_array($ri) ? ($ri['assigned_manpower'] ?? 0) : 0)); };
    $row_role = function ($ri) { return is_object($ri) ? ($ri->manpower_role ?? null) : (is_array($ri) ? ($ri['manpower_role'] ?? null) : null); };
    $row_notes = function ($ri) { return is_object($ri) ? ($ri->manpower_notes ?? ($ri->notes ?? null)) : (is_array($ri) ? ($ri['manpower_notes'] ?? ($ri['notes'] ?? null)) : null); };
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>Borrow Request #{{ $r->id ?? '' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.3; }
        /* Header table for dompdf compatibility (stable cross-PDF engines) */
        table.hdr { width:100%; border-collapse:collapse; }
        table.hdr td { vertical-align:middle; }
        table.hdr td.left { text-align:left; width: 140px; }
        table.hdr td.center { text-align:center; }
        table.hdr td.right { text-align:right; width: 140px; }
        .logo { height: 100px; width: auto; object-fit: contain; display:inline-block; }
        .title { text-align: center; }
        .title h1 { margin: 0; font-size: 16px; letter-spacing: 0.4px; color: #4c1d95; }
        .title .sub { margin-top: 2px; font-size: 10px; color: #6b7280; }
        .accent { height: 4px; background: linear-gradient(90deg, #6d28d9, #4c1d95); border-radius: 2px; margin: 8px 0 10px; }

        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-top: 10px; }
        .card h3 { margin: 0 0 6px 0; font-size: 12px; color: #374151; text-transform: uppercase; letter-spacing: .4px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px 14px; }
        .field .label { font-size: 10px; color: #6b7280; margin-bottom: 2px; }
        .field .value { background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 6px 8px; min-height: 20px; }

        table.list { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10px; }
        table.list th, table.list td { border: 1px solid #e5e7eb; padding: 6px 8px; }
        table.list th { background: #f5f3ff; color: #4338ca; text-align: left; font-weight: 700; }
        table.list td.center, table.list th.center { text-align: center; }
        table.list tr:nth-child(even) td { background: #fafafc; }

        .sign-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-top: 12px; }
        .sign .label { color: #6b7280; font-size: 10px; margin-bottom: 8px; }
        .line { border-top: 1px solid #111827; padding-top: 6px; text-align: center; min-height: 36px; }
        .approved { text-align: center; margin-top: 12px; }
        .muted { color: #6b7280; font-size: 10px; }
        footer { position: fixed; bottom: 10mm; left: 12mm; right: 12mm; text-align: center; font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
    <table class="hdr" role="presentation" aria-hidden="true">
        <tr>
            <td class="left">
                @if($forPdf && file_exists($logoRightFs))
                    <img src="{{ $logoRightFs }}" alt="Left logo" class="logo">
                @else
                    <img src="{{ asset('images/logo2.png') }}" alt="Left logo" class="logo">
                @endif
            </td>
            <td class="center">
                <div class="title">
                    <h1>OFFICE OF GENERAL SERVICES</h1>
                    <div class="sub">Municipality of Tagoloan – Province of Misamis Oriental</div>
                    <div class="sub" style="margin-top:4px; font-weight:700; color:#4c1d95;">BORROW REQUEST FORM</div>
                </div>
            </td>
            <td class="right">
                @if($forPdf && file_exists($logoLeftFs))
                    <img src="{{ $logoLeftFs }}" alt="Right logo" class="logo">
                @else
                    <img src="{{ asset('images/logo.png') }}" alt="Right logo" class="logo">
                @endif
            </td>
        </tr>
    </table>
    <div class="accent"></div>

    <!-- Requester Information -->
    <section class="card">
        <h3>Borrower Information</h3>
        <div class="grid">
            <div class="field">
                <div class="label">Address</div>
                <div class="value">{{ optional($r->user)->address ?? ($r->address ?? '') }}</div>
            </div>
            <div class="field">
                <div class="label">Contact Number</div>
                <div class="value">{{ optional($r->user)->phone ?? ($r->contact_number ?? '') }}</div>
            </div>
            <div class="field" style="grid-column:1 / -1;">
                <div class="label">Purpose</div>
                <div class="value">{{ $r->purpose ?? '' }}</div>
            </div>
        </div>
    </section>

    <!-- Schedule -->
    <section class="card">
        <h3>Schedule</h3>
        <div class="grid-3">
            <div class="field">
                <div class="label">Date Borrowed</div>
                <div class="value">{{ $fmtDate($r->borrow_date ?? null) }}</div>
            </div>
            <div class="field">
                <div class="label">Time of Usage</div>
                <div class="value">{{ $r->time_of_usage ?? '' }}</div>
            </div>
            <div class="field">
                <div class="label">Date to Return (No. of days)</div>
                <div class="value">{{ $fmtDate($r->return_date ?? null) }}@if(!empty($r->return_days)) ({{ $r->return_days }} days) @endif</div>
            </div>
        </div>
    </section>

    <!-- Items Requested -->
    <section class="card">
        <h3>Request Details</h3>
        <table class="list" role="table" aria-label="Request details">
            <thead>
                <tr>
                    <th class="center" style="width:8%;">ID</th>
                    <th>Item</th>
                    <th class="center" style="width:10%;">Qty</th>
                    <th class="center" style="width:14%;">Manpower</th>
                    <th style="width:18%;">Role</th>
                    <th style="width:22%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @if(count($items))
                    @foreach($items as $idx => $it)
                        @php
                            $itemId = ($row_item_id)($it) ?? ($idx + 1);
                            $iname  = ($row_item_name)($it) ?? 'Item';
                            $iqty   = ($row_qty)($it) ?? '';
                            $assigned = ($row_assigned)($it);
                            $role = ($row_role)($it);
                            $notes = ($row_notes)($it);
                        @endphp
                        <tr>
                            <td class="center">{{ $itemId }}</td>
                            <td>{{ $iname }}</td>
                            <td class="center">{{ $iqty }}</td>
                            <td class="center">@if($assigned>0) {{ $assigned }} @endif</td>
                            <td>{{ $role ?? '' }}</td>
                            <td>{{ $notes ?? '' }}</td>
                        </tr>
                    @endforeach
                @else
                    @for($i=0;$i<8;$i++)
                        <tr>
                            <td class="center">&nbsp;</td>
                            <td>&nbsp;</td>
                            <td class="center">&nbsp;</td>
                            <td class="center">&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    @endfor
                @endif
            </tbody>
        </table>
    </section>

    <!-- Accountability Note -->
    <section class="card" style="padding:10px;">
        <div class="muted">
            I/We will be accountable for any damage incurred in the equipment/s and will return the equipment/materials promptly and in the same working condition it was borrowed.
        </div>
    </section>

    <!-- Signatures -->
    <section class="card">
        <div class="sign-row">
            <div class="sign">
                <div class="label">Borrower's Name and Signature</div>
                <div class="line">{{ trim(optional($r->user)->first_name . ' ' . (optional($r->user)->last_name ?? '')) }}</div>
            </div>
            <div class="sign">
                <div class="label">Verified by</div>
                <div class="line">
                    <div style="font-weight:700;">{{ $verifiedByName }}</div>
                    <div class="muted">{{ $verifiedByTitle }}</div>
                </div>
            </div>
            <div class="sign">
                <div class="label">Noted by</div>
                <div class="line">
                    <div style="font-weight:700;">{{ $notedByName }}</div>
                    <div class="muted">{{ $notedByTitle }}</div>
                </div>
            </div>
        </div>
        <div class="approved">
            <div class="muted">Approved by</div>
            <div style="font-weight:700; margin-top:6px;">{{ $approvedByName }}</div>
            <div class="muted">{{ $approvedByTitle }}</div>
        </div>
    </section>

    <footer>
        Present this printed slip with a valid government-issued ID when collecting items. Request ID: #{{ $r->id ?? '' }}
    </footer>
</body>
</html>
