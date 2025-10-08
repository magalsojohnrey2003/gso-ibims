<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] ?? 'Report' }}</title>
    <style>
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 6px; }
        .meta { margin-bottom: 8px; color: #444; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top; }
    th { background: #1F4E79; color: #ffffff; font-weight: 600; }
        .small { font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <h1>{{ $meta['title'] ?? 'Report' }}</h1>
    <div class="meta small">
        <div><strong>Period:</strong> {{ $meta['start'] }} → {{ $meta['end'] }}</div>
        <div><strong>Generated:</strong> {{ $meta['generated_at'] }}</div>
    </div>

    <table>
        <thead>
            <tr>
                @if(!empty($columns))
                    @foreach($columns as $c)
                        <th>{{ $c }}</th>
                    @endforeach
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
                        {{-- fallback: print object properties in order if object --}}
                        @foreach((array)$row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ max(1, count($columns)) }}">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
