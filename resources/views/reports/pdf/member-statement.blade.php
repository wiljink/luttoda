<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement of Account - {{ $member['name'] }} ({{ $year }})</title>
    <style>
        @page { margin: 96px 44px 64px 44px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #2b2b2b; margin: 0; }

        header {
            position: fixed; top: -76px; left: 0; right: 0; height: 66px;
            border-bottom: 2px solid #14532d; padding-bottom: 8px;
        }
        header .brand { font-size: 17px; font-weight: bold; color: #14532d; letter-spacing: 0.5px; }
        header .brand-sub { font-size: 9px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
        header .meta { text-align: right; font-size: 10px; color: #555; }
        header table { width: 100%; border: none; margin: 0; }
        header td { border: none; padding: 0; vertical-align: bottom; }

        footer {
            position: fixed; bottom: -46px; left: 0; right: 0; height: 36px;
            border-top: 1px solid #ddd; padding-top: 6px; font-size: 9px; color: #999;
        }
        footer table { width: 100%; border: none; }
        footer td { border: none; padding: 0; }
        .page-number:before { content: "Page " counter(page) " of " counter(pages); }

        h1 { font-size: 19px; margin: 0 0 2px 0; color: #1a1a1a; }
        .subtitle { color: #666; font-size: 12px; margin-bottom: 16px; }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { border: 1px solid #e2e2e2; padding: 7px 10px; width: 25%; }
        .info-label { display: block; font-size: 8.5px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
        .info-value { font-size: 12px; font-weight: bold; color: #1a1a1a; }

        h2 {
            font-size: 12px; color: #14532d; margin: 18px 0 7px 0;
            border-bottom: 1px solid #cfe3d5; padding-bottom: 3px;
        }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.data th, table.data td { border: 1px solid #e2e2e2; padding: 6px 8px; text-align: left; }
        table.data th { background-color: #f5f7f5; font-size: 9px; text-transform: uppercase; letter-spacing: 0.3px; color: #555; }
        table.data tbody tr:nth-child(even) { background-color: #fafafa; }
        .text-end { text-align: right; }
        tfoot td { background-color: #f0f4f0; font-weight: bold; }

        .kv { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .kv td { border: 1px solid #e2e2e2; padding: 6px 10px; }
        .kv td.k { color: #555; width: 68%; }
        .kv td.v { text-align: right; font-weight: bold; width: 32%; }

        .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 8.5px; font-weight: bold; background: #e9ecef; color: #444; }
        .empty { color: #999; font-style: italic; padding: 4px 0 10px 0; }

        .totals { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .totals td { padding: 9px 12px; background: #eef3ee; }
        .totals td.label { font-weight: bold; color: #1a1a1a; border-radius: 4px 0 0 4px; }
        .totals td.value { text-align: right; font-size: 15px; font-weight: bold; color: #14532d; border-radius: 0 4px 4px 0; }

        .sign { width: 100%; border: none; margin-top: 42px; }
        .sign td { border: none; width: 50%; padding: 0 18px; font-size: 10px; color: #555; text-align: center; }
        .sign .line { border-top: 1px solid #333; padding-top: 4px; }
    </style>
</head>
<body>
    <header>
        <table>
            <tr>
                <td>
                    <span class="brand">LUTTODA</span><br>
                    <span class="brand-sub">Jeepney / Tricycle Operators &amp; Drivers Association</span>
                </td>
                <td class="meta">
                    Statement of Account<br>
                    Generated: {{ $generatedAt->format('M d, Y h:i A') }}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table>
            <tr>
                <td>LUTTODA — Statement of Account · {{ $member['name'] }}</td>
                <td class="text-end page-number"></td>
            </tr>
        </table>
    </footer>

    <h1>Statement of Account</h1>
    <div class="subtitle">For the year {{ $year }} (January 1 – December 31, {{ $year }})</div>

    <table class="info-table">
        <tr>
            <td><span class="info-label">Member No.</span><span class="info-value">{{ $member['member_no'] }}</span></td>
            <td><span class="info-label">Name</span><span class="info-value">{{ $member['name'] }}</span></td>
            <td><span class="info-label">Route</span><span class="info-value">{{ $member['route'] ?: '—' }}</span></td>
            <td><span class="info-label">Plate No.</span><span class="info-value">{{ $member['plate_number'] ?: '—' }}</span></td>
        </tr>
    </table>

    <h2>Daily Dues</h2>
    <table class="kv">
        <tr><td class="k">Days paid</td><td class="v">{{ $days_paid }}</td></tr>
        <tr><td class="k">Tickets bought</td><td class="v">{{ $tickets_total }}</td></tr>
        <tr><td class="k">Total dues paid</td><td class="v">₱{{ number_format($daily_dues_total, 2) }}</td></tr>
        <tr><td class="k">&nbsp;&nbsp;&nbsp;→ to savings</td><td class="v">₱{{ number_format($savings_contributed, 2) }}</td></tr>
        <tr><td class="k">&nbsp;&nbsp;&nbsp;→ member's share</td><td class="v">₱{{ number_format($members_share_contributed, 2) }}</td></tr>
        <tr><td class="k">&nbsp;&nbsp;&nbsp;→ association fund</td><td class="v">₱{{ number_format($association_contributed, 2) }}</td></tr>
    </table>

    <h2>Fuel (Diesel) Consumption</h2>
    <table class="kv">
        <tr><td class="k">Total litres purchased ({{ $year }})</td><td class="v">{{ number_format($fuel_total_liters, 2) }} L</td></tr>
        <tr><td class="k">Fuel rebate earned</td><td class="v">₱{{ number_format($fuel_rebate_earned, 2) }}</td></tr>
    </table>

    <h2>Alkansiya Program (Voluntary SSS Savings)</h2>
    @if (count($alkansiya_entries))
        <table class="data">
            <thead><tr><th style="width:22%">Date</th><th class="text-end" style="width:22%">Amount</th><th>Remarks</th></tr></thead>
            <tbody>
                @foreach ($alkansiya_entries as $a)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($a['date'])->format('M d, Y') }}</td>
                        <td class="text-end">₱{{ number_format($a['amount'], 2) }}</td>
                        <td>{{ $a['remarks'] ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td class="text-end">Contributed in {{ $year }}</td><td class="text-end">₱{{ number_format($alkansiya_total, 2) }}</td><td></td></tr>
            </tfoot>
        </table>
    @else
        <p class="empty">No Alkansiya contributions recorded for {{ $year }}.</p>
    @endif
    <table class="kv">
        <tr><td class="k">Alkansiya balance to date (all years)</td><td class="v">₱{{ number_format($alkansiya_balance, 2) }}</td></tr>
    </table>

    <h2>Loans</h2>
    @if (count($loans))
        <table class="data">
            <thead>
                <tr>
                    <th style="width:16%">Date</th><th style="width:12%">Type</th>
                    <th class="text-end">Amount</th><th class="text-end">Payable</th>
                    <th class="text-end">Paid</th><th class="text-end">Balance</th><th style="width:12%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($loans as $l)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($l['date'])->format('M d, Y') }}</td>
                        <td>{{ ucfirst($l['type']) }}</td>
                        <td class="text-end">₱{{ number_format($l['amount'], 2) }}</td>
                        <td class="text-end">₱{{ number_format($l['total_payable'], 2) }}</td>
                        <td class="text-end">₱{{ number_format($l['paid'], 2) }}</td>
                        <td class="text-end">₱{{ number_format($l['balance'], 2) }}</td>
                        <td><span class="badge">{{ ucfirst($l['status']) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="empty">No loans on record for {{ $year }}.</p>
    @endif

    <h2>Benefit Claims</h2>
    @if (count($benefits))
        <table class="data">
            <thead><tr><th>Type</th><th class="text-end" style="width:22%">Amount</th><th style="width:22%">Status</th></tr></thead>
            <tbody>
                @foreach ($benefits as $b)
                    <tr>
                        <td>{{ ucfirst($b['type']) }}</td>
                        <td class="text-end">₱{{ number_format($b['amount'], 2) }}</td>
                        <td><span class="badge">{{ ucfirst($b['status']) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="empty">No benefit claims for {{ $year }}.</p>
    @endif

    <h2>Savings Summary</h2>
    <table class="kv">
        <tr><td class="k">Savings contributed from dues ({{ $year }})</td><td class="v">₱{{ number_format($savings_contributed, 2) }}</td></tr>
        <tr><td class="k">Ledger entries in {{ $year }}</td><td class="v">{{ $savings_ledger_entries }}</td></tr>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Current Savings Balance</td>
            <td class="value">₱{{ number_format($savings_balance, 2) }}</td>
        </tr>
    </table>
    <table class="totals">
        <tr>
            <td class="label">Current Alkansiya (SSS) Balance</td>
            <td class="value">₱{{ number_format($alkansiya_balance, 2) }}</td>
        </tr>
    </table>

    <table class="sign">
        <tr>
            <td><div class="line">Member's Signature</div></td>
            <td><div class="line">Treasurer</div></td>
        </tr>
    </table>
</body>
</html>
