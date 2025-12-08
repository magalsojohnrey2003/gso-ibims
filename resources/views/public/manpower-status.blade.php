<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manpower Request Status</title>
    <style>
        :root {
            color-scheme: light;
            --accent: #4c1d95;
            --accent-light: #ede9fe;
            --border: #e5e7eb;
            --text: #1f2937;
            --muted: #6b7280;
        }
        * { box-sizing: border-box; font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        body {
            margin: 0;
            padding: 1.5rem;
            background: #f5f5f5;
            color: var(--text);
        }
        .card {
            max-width: 480px;
            margin: 0 auto;
            background: #fff;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.2);
            border: 1px solid var(--border);
        }
        h1 {
            font-size: 1.5rem;
            margin: 0 0 0.25rem;
            color: var(--accent);
        }
        dl {
            margin: 1.5rem 0 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
        }
        dt {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            margin-bottom: 0.35rem;
        }
        dd {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text);
        }
        .footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.8rem;
            color: var(--muted);
        }
        .address-line {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text);
            line-height: 1.35;
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Request #{{ $request->id }}</h1>

        <dl>
            <div>
                <dt>Role</dt>
                <dd>{{ $request->role }}</dd>
            </div>
            <div>
                <dt>Requested Qty</dt>
                <dd>{{ $request->quantity }}</dd>
            </div>
            <div>
                <dt>Approved Qty</dt>
                <dd>{{ $request->approved_quantity ?? '—' }}</dd>
            </div>
            <div>
                <dt>Schedule</dt>
                <dd>
                    @php
                        $start = optional($request->start_at)->format('M. j, Y');
                        $end = optional($request->end_at)->format('M. j, Y');
                    @endphp
                    {{ $start && $end ? $start . ' - ' . $end : '—' }}
                </dd>
            </div>
            <div>
                <dt>Location</dt>
                <dd>
                    <div class="address-line">{{ trim(($request->municipality ? $request->municipality . ', ' : '') . ($request->barangay ?? '')) ?: '—' }}</div>
                    @if($request->location)
                        <div class="address-line">{{ $request->location }}</div>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Assigned Personnel</dt>
                <dd>
                    @php
                        $names = is_array($request->assigned_personnel_names)
                            ? array_filter(array_map(fn($v) => trim((string) $v), $request->assigned_personnel_names))
                            : [];
                    @endphp
                    {{ $names ? implode(', ', $names) : '—' }}
                </dd>
            </div>
        </dl>

        <div class="footer">
            Updated {{ optional($request->updated_at)->format('M. j, Y h:i A') }}
        </div>
    </main>
</body>
</html>
