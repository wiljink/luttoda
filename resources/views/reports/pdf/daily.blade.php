<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Report - {{ $date }}</title>
    <style>
        @page {
            margin: 90px 40px 70px 40px;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #2b2b2b;
            margin: 0;
        }

        /* ---------- Repeating header ---------- */
        header {
            position: fixed;
            top: -70px;
            left: 0;
            right: 0;
            height: 60px;
            border-bottom: 2px solid #14532d;
            padding-bottom: 8px;
        }

        header .brand {
            font-size: 17px;
            font-weight: bold;
            color: #14532d;
            letter-spacing: 0.5px;
        }

        header .brand-sub {
            font-size: 9px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        header .report-meta {
            text-align: right;
            font-size: 10px;
            color: #555;
        }

        header table { width: 100%; border: none; margin: 0; }
        header td { border: none; padding: 0; vertical-align: bottom; }

        /* ---------- Repeating footer ---------- */
        footer {
            position: fixed;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 40px;
            border-top: 1px solid #ddd;
            padding-top: 6px;
            font-size: 9px;
            color: #999;
        }

        footer table { width: 100%; border: none; }
        footer td { border: none; padding: 0; }
        .page-number:before { content: "Page " counter(page) " of " counter(pages); }

        /* ---------- Title block ---------- */
        .title-block {
            margin-bottom: 18px;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 3px 0;
            color: #1a1a1a;
        }

        .subtitle {
            color: #666;
            font-size: 12px;
        }

        /* ---------- Summary strip ---------- */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }

        .summary-table td {
            border: 1px solid #e2e2e2;
            border-radius: 4px;
            padding: 10px 14px;
            width: 33.33%;
        }

        .summary-label {
            display: block;
            font-size: 9px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #14532d;
        }

        /* ---------- Route sections ---------- */
        .route-block {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .route-title {
            background-color: #14532d;
            color: #ffffff;
            font-weight: bold;
            padding: 7px 10px;
            border-radius: 4px 4px 0 0;
            font-size: 11px;
        }

        .route-title .count {
            float: right;
            font-weight: normal;
            font-size: 9px;
            color: #cfe3d5;
        }

        table.dues {
            width: 100%;
            border-collapse: collapse;
        }

        table.dues th, table.dues td {
            border: 1px solid #e2e2e2;
            padding: 6px 8px;
            text-align: left;
        }

        table.dues th {
            background-color: #f5f7f5;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #555;
        }

        table.dues tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .text-end { text-align: right; }

        tfoot td, tfoot th {
            background-color: #f0f4f0;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
        }

        .paid { background-color: #d1e7dd; color: #0f5132; }
        .pending { background-color: #fff3cd; color: #664d03; }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #999;
            border: 1px dashed #ddd;
            border-radius: 6px;
        }

        .grand-total-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .grand-total-table td {
            border: none;
            padding: 10px 14px;
        }

        .grand-total-table .label {
            font-size: 12px;
            font-weight: bold;
            color: #1a1a1a;
            background-color: #eef3ee;
            border-radius: 4px 0 0 4px;
        }

        .grand-total-table .value {
            font-size: 16px;
            font-weight: bold;
            color: #14532d;
            text-align: right;
            background-color: #eef3ee;
            border-radius: 0 4px 4px 0;
        }
    </style>
</head>
<body>

    <header>
        <table>
            <tr>
                <td>
                    <span class="brand">LUTTODA</span><br>
                    <span class="brand-sub">Daily Collection Report</span>
                </td>
                <td class="report-meta">
                    Report Date: {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}<br>
                    Generated: {{ now()->format('M d, Y h:i A') }}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table>
            <tr>
                <td>LUTTODA Daily Collection Report</td>
                <td class="text-end page-number"></td>
            </tr>
        </table>
    </footer>

    <div class="title-block">
        <h1>Daily Collection Report</h1>
        <div class="subtitle">{{ \Carbon\Carbon::parse($date)->format('l, F d, Y') }}</div>
    </div>

    @php
        $totalCollected = $dues->flatten()->sum('amount');
        $totalRoutes = $dues->count();
        $totalEntries = $dues->flatten()->count();
    @endphp

    <table class="summary-table">
        <tr>
            <td>
                <span class="summary-label">Total Collected</span>
                <span class="summary-value">₱{{ number_format($totalCollected, 2) }}</span>
            </td>
            <td>
                <span class="summary-label">Routes Covered</span>
                <span class="summary-value">{{ $totalRoutes }}</span>
            </td>
            <td>
                <span class="summary-label">Total Entries</span>
                <span class="summary-value">{{ $totalEntries }}</span>
            </td>
        </tr>
    </table>

    @forelse($dues as $route => $routeDues)
        <div class="route-block">
            <div class="route-title">
                Route: {{ $route ?: 'Unassigned' }}
                <span class="count">{{ $routeDues->count() }} {{ Str::plural('entry', $routeDues->count()) }}</span>
            </div>
            <table class="dues">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 35%;">Member</th>
                        <th style="width: 20%;">Member No.</th>
                        <th style="width: 20%;" class="text-end">Amount</th>
                        <th style="width: 20%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($routeDues as $i => $due)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $due->member->name ?? 'N/A' }}</td>
                            <td>{{ $due->member->member_no ?? '-' }}</td>
                            <td class="text-end">₱{{ number_format($due->amount, 2) }}</td>
                            <td>
                                <span class="badge {{ $due->status === 'paid' ? 'paid' : 'pending' }}">
                                    {{ ucfirst($due->status ?? 'pending') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end">Route Subtotal</td>
                        <td class="text-end">₱{{ number_format($routeDues->sum('amount'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @empty
        <div class="empty-state">No dues recorded for {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}.</div>
    @endforelse

    @if($dues->count())
        <table class="grand-total-table">
            <tr>
                <td class="label">Grand Total Collected</td>
                <td class="value">₱{{ number_format($totalCollected, 2) }}</td>
            </tr>
        </table>
    @endif

</body>
</html>
