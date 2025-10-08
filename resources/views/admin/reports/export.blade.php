<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Item</th>
            <th>Category</th>
            <th>Quantity</th>
            <th>Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($reports as $report)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $report->item_name ?? 'N/A' }}</td>
                <td>{{ $report->category ?? 'N/A' }}</td>
                <td>{{ $report->quantity ?? 0 }}</td>
                <td>{{ $report->date ?? '-' }}</td>
                <td>{{ ucfirst($report->status ?? '-') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
