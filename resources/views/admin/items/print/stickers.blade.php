@php
    $stickers = $stickers ?? collect();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Property Stickers - {{ $item->name ?? 'Item' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 15mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-gap: 12mm 10mm;
        }

        .sticker {
            border: 1px solid #9ca3af;
            border-radius: 6px;
            padding: 10px 12px;
            min-height: 110px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .header {
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .property-number {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .segments {
            display: flex;
            gap: 4px;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .segments span {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 4px;
            padding: 2px 6px;
            display: inline-block;
            min-width: 32px;
            text-align: center;
        }

        .meta {
            font-size: 10px;
            line-height: 1.4;
            color: #4b5563;
        }
    </style>
</head>
<body>
    <div class="grid">
        @foreach($stickers as $index => $sticker)
            <div class="sticker">
                <div>
                    <div class="header">{{ $item->name ?? 'Inventory Item' }}</div>
                    <div class="property-number">
                        {{ $sticker['property_number'] ?: 'N/A' }}
                    </div>
                    <div class="segments">
                        <span id="print_yp_{{ $index }}">{{ $sticker['year'] ?: '----' }}</span>
                        <span id="print_ppe_{{ $index }}">{{ $sticker['category'] ?: '----' }}</span>
                        <span id="print_gla_{{ $index }}">{{ $sticker['gla'] ?: '----' }}</span>
                        <span id="print_serial_{{ $index }}">{{ $sticker['serial'] ?: '----' }}</span>
                        <span id="print_office_{{ $index }}">{{ $sticker['office'] ?: '----' }}</span>
                    </div>
                </div>
                <div class="meta"> 
                    <div id="print_mn_{{ $index }}"><strong>Model No.:</strong> {{ $sticker['model_no'] ?: '—' }}</div>
                    <div id="print_sn_{{ $index }}"><strong>Serial No.:</strong> {{ $sticker['serial_no'] ?: '—' }}</div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
