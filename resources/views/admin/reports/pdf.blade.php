<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] ?? 'Report' }}</title>
    <style>
        /* Page & font */
        @page { margin: 40px 40px 60px 40px; } /* top right bottom left */
        body {
            font-family: "Times New Roman", DejaVu Serif, serif;
            font-size: 11.5px;
            color: #111827;
            margin: 0;
            padding-bottom: 90px;
            -webkit-print-color-adjust: exact;
        }

        .report-header {
            width: 100%;
            display: table;
            table-layout: fixed;
            margin-bottom: 30px;
        }
        .header-cell {
            display: table-cell;
            vertical-align: middle;
            padding: 0 10px;
        }
        .header-left,
        .header-right {
            width: 18%;
        }
        .header-center {
            width: 64%;
            text-align: center;
            padding-top: 12px;
        }
        .logo {
            max-height: 110px;
            max-width: 100%;
        }

        .gov-line {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .gov-line strong {
            font-size: 12px;
        }
        .agency-line {
            font-size: 13px;
            font-weight: bold;
            margin: 2px 0 6px 0;
            letter-spacing: 1.2px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #4C1D95;
            margin: 10px 0 4px 0;
            letter-spacing: 1.6px;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 32px;
            margin-bottom: 12px;
            page-break-inside: auto;
        }
        table.report-table thead { display: table-header-group; }
        table.report-table tbody  { display: table-row-group; }
        table.report-table th,
        table.report-table td {
            border: 1px solid #E5E7EB;
            padding: 4px 6px;
            font-size: 9px;
            text-align: center;
        }
        table.report-table {
            border: 1px solid #E5E7EB;
        }
        table.report-table thead th {
            background: #7C3AED;
            color: #FFFFFF;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: center;
            white-space: nowrap;
            font-size: 9.5px;
        }
        table.report-table tbody tr:nth-child(even) {
            background: #F5F7FF;
        }
        table.report-table tbody td {
            word-break: break-word;
        }
        table.report-table thead th:first-child,
        table.report-table thead th:last-child,
        table.report-table tbody tr:last-child td:first-child,
        table.report-table tbody tr:last-child td:last-child {
            border-radius: 0;
        }
        table.report-table th.property-number,
        table.report-table td.property-number {
            white-space: nowrap;
            word-break: normal;
            hyphens: none;
        }
        table.report-table tr.nothing-follows td {
            font-weight: bold;
            text-align: center;
            padding-top: 16px;
            padding-bottom: 16px;
            border-top: 1px solid #C7D2FE;
        }

        .pdf-footer {
            position: fixed;
            bottom: 12px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 10px;
            color: #6B7280;
        }
        .separator-line {
            height: 1px;
            background: #E5E7EB;
            margin-bottom: 6px;
        }
        .page-number {
            font-weight: 600;
            letter-spacing: 0.8px;
            color: #4338CA;
        }
        .page-number::before {
            content: counter(page);
        }

    </style>
</head>
<body>
    {{-- Header block with optional logos --}}
    <div class="report-header">
        <div class="header-cell header-left">
            @if(file_exists(public_path('images/logo.png')))
                <img class="logo" src="{{ public_path('images/logo.png') }}" alt="Logo left">
            @endif
        </div>

        <div class="header-cell header-center">
            <p class="gov-line">Republic of the Philippines</p>
            <p class="gov-line">Province of Misamis Oriental</p>
            <p class="gov-line">Municipality of Tagoloan</p>
            <p class="agency-line">Office of General Services</p>
            <p class="report-title">{{ strtoupper($meta['title'] ?? 'Request Form') }}</p>
        </div>

        <div class="header-cell header-right" style="text-align:right;">
            @if(file_exists(public_path('images/logo2.png')))
                <img class="logo" src="{{ public_path('images/logo2.png') }}" alt="Logo right">
            @endif
        </div>
    </div>

    {{-- Table --}}
    @php
        $columnClasses = [];
        foreach (($columns ?? []) as $index => $heading) {
            if (is_string($heading) && preg_match('/property\s*(number|no\.?|#)/i', $heading)) {
                $columnClasses[$index] = 'property-number';
            }
        }
    @endphp
    <table class="report-table">
        <thead>
            <tr>
                @if(!empty($columns))
                    @foreach($columns as $i => $c)
                        <th class="{{ $columnClasses[$i] ?? '' }}">{{ $c }}</th>
                    @endforeach
                @else
                    <th>No columns</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @if(is_array($row))
                        @foreach($row as $idx => $cell)
                            <td class="{{ $columnClasses[$idx] ?? '' }}">{{ $cell === null || $cell === '' ? '—' : $cell }}</td>
                        @endforeach
                    @else
                        @foreach((array)$row as $idx => $cell)
                            <td class="{{ $columnClasses[$idx] ?? '' }}">{{ $cell === null || $cell === '' ? '—' : $cell }}</td>
                        @endforeach
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(1, count($columns)) }}" style="text-align:center">No records found.</td>
                </tr>
            @endforelse
            @if(!empty($rows))
                <tr class="nothing-follows">
                    <td colspan="{{ max(1, count($columns)) }}">*** Nothing Follows ***</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- Meta info below the table --}}
    <div class="report-meta">
        <div class="meta-block"><strong>Period:</strong> {{ $meta['start'] ?? '-' }} → {{ $meta['end'] ?? '-' }}</div>
        <div class="meta-block"><strong>Generated:</strong> {{ $meta['generated_at'] ?? '-' }}</div>
    </div>

    {{-- Footer separator only (page numbers rendered by Dompdf canvas in controller) --}}
    <div class="pdf-footer">
        <div class="separator-line"></div>
        <div class="page-number"></div>
    </div>

</body>
</html>
