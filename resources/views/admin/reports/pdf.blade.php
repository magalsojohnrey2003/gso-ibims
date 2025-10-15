<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] ?? 'Report' }}</title>
    <style>
        /* Page & font */
        @page { margin: 40px 40px 60px 40px; } /* top right bottom left */
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #222;
            margin: 0;
            -webkit-print-color-adjust: exact;
        }

        /* Header (logo + centered title) */
        .report-header {
            width: 100%;
            display: table;
            table-layout: fixed;
            margin-bottom: 12px;
        }
        .header-cell {
            display: table-cell;
            vertical-align: middle;
            padding: 0 8px;
        }
        .header-left, .header-right {
            width: 18%;
        }
        .header-center {
            width: 64%;
            text-align: center;
        }
        .logo {
            max-height: 72px;
            max-width: 100%;
        }

        /* Title lines */
        .org-title {
            font-size: 16px;
            font-weight: 700;
            color: #4C1D95; /* government purple (adjust if you'd like darker/lighter) */
            margin: 0 0 4px 0;
            letter-spacing: 0.4px;
        }
        .org-sub {
            font-size: 11.5px;
            color: #333;
            margin: 0 0 6px 0;
        }
        .report-label {
            font-size: 12px;
            font-weight: 700;
            color: #6B21A8; /* purple accent for small label */
            letter-spacing: 0.5px;
            margin-top: 6px;
        }

        /* Table styles */
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            page-break-inside: auto;
        }
        table.report-table thead { display: table-header-group; } /* force repeat */
        table.report-table tbody  { display: table-row-group; }
        table.report-table th,
        table.report-table td {
            border: 1px solid #E8E6F0;
            padding: 8px 10px;
            vertical-align: top;
            font-size: 11px;
        }
        table.report-table thead th {
            background: #6B21A8; /* purple header */
            color: #fff;
            font-weight: 700;
            text-align: left;
        }
        table.report-table tbody tr:nth-child(even) {
            background: #faf7ff;
        }

        /* Meta shown under the table */
        .report-meta {
            margin-top: 12px;
            font-size: 11px;
            color: #444;
        }
        .report-meta .meta-block {
            margin-bottom: 3px;
        }

        /* Footer with page number - fixed */
        .pdf-footer {
            position: fixed;
            bottom: 12px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
        .separator-line {
            height: 1px;
            background: #E6E1F5;
            margin-bottom: 6px;
        }

        /* Small helpers */
        .muted { color: #666; font-size: 11px; }

    </style>
</head>
<body>
    {{-- Header: left logo, centered title, right logo --}}
    <div class="report-header">
        <div class="header-cell header-left">
            @if(file_exists(public_path('images/logo.png')))
                <img class="logo" src="{{ public_path('images/logo.png') }}" alt="Logo left">
            @endif
        </div>

        <div class="header-cell header-center">
            <div class="org-title">OFFICE OF GENERAL SERVICES</div>
            <div class="org-sub">Municipality of Tagoloan - Province of Misamis Oriental</div>
            <div class="report-label">{{ $meta['title'] ?? 'Report' }}</div>
        </div>

        <div class="header-cell header-right" style="text-align:right;">
            @if(file_exists(public_path('images/logo2.png')))
                <img class="logo" src="{{ public_path('images/logo2.png') }}" alt="Logo right">
            @endif
        </div>
    </div>

    {{-- Table --}}
    <table class="report-table">
        <thead>
            <tr>
                @if(!empty($columns))
                    @foreach($columns as $c)
                        <th>{{ $c }}</th>
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
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    @else
                        @foreach((array)$row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(1, count($columns)) }}" style="text-align:center">No records found.</td>
                </tr>
            @endforelse
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
    </div>

</body>
</html>
