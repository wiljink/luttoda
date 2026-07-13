<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fuel Consumption Report - {{ $date }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        .subtitle { color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-end { text-align: right; }
        tfoot th { background-color: #f8f9fa; }
        .footer-note { margin-top: 30px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <h1>Fuel Consumption Report</h1>
    <div class="subtitle">{{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Member</th>
                <th>Vehicle</th>
                <th>Liters</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $i => $record)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($record->consumption_date)->format('M d, Y') }}</td>
                    <td>{{ $record->member->name ?? 'N/A' }}</td>
                    <td>{{ $record->vehicle_no ?? '-' }}</td>
                    <td>{{ $record->liters ?? '-' }}</td>
                    <td class="text-end">₱{{ number_format($record->amount ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No fuel records found.</td>
                </tr>
            @endforelse
        </tbody>
        @if($records->count())
            <tfoot>
                <tr>
                    <th colspan="5" class="text-end">Total</th>
                    <th class="text-end">₱{{ number_format($records->sum('amount'), 2) }}</th>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer-note">Generated on {{ now()->format('F d, Y h:i A') }}</div>
</body>
</html>
