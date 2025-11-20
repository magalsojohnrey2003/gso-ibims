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
        .subtitle {
            color: var(--muted);
            margin-bottom: 1.5rem;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-pill .dot { font-size: 0.8rem; }
        .status-pill.pending { background: #fef3c7; color: #92400e; }
        .status-pill.approved { background: #d1fae5; color: #065f46; }
        .status-pill.rejected { background: #fee2e2; color: #991b1b; }
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
    </style>
</head>
<body>
    <main class="card">
        <h1>Request #{{ $request->id }}</h1>
        <div class="subtitle">Public Manpower Request Status</div>

        <div class="status-pill {{ $request->status }}">
            <span class="dot">●</span>
            <span>{{ ucfirst($request->status) }}</span>
        </div>

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
                    {{ trim(($request->municipality ? $request->municipality . ', ' : '') . ($request->barangay ?? '')) ?: '—' }}<br>
                    @if($request->location)
                        <small style="color: var(--muted); font-size: 0.85rem;">{{ $request->location }}</small>
                    @endif
                </dd>
            </div>
        </dl>

        <div class="footer">
            Updated {{ optional($request->updated_at)->format('M. j, Y h:i A') }}
        </div>
    </main>
</body>
</html>
