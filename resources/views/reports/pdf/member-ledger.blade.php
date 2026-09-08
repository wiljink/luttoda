<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Savings Ledger - {{ $member->full_name }}</title>
    <style>
        @page { margin: 90px 44px 60px 44px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #2b2b2b; margin: 0; }

        header {
            position: fixed; top: -70px; left: 0; right: 0; height: 60px;
            border-bottom: 2px solid #14532d; padding-bottom: 8px;
        }
        header .brand { font-size: 17px; font-weight: bold; color: #14532d; }
        header .brand-sub { font-size: 9px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
        header .meta { text-align: right; font-size: 10px; color: #555; }
        header table { width: 100%; border: none; } header td { border: none; padding: 0; vertical-align: bottom; }

        footer { position: fixed; bottom: -44px; left: 0; right: 0; height: 34px; border-top: 1px solid #ddd; padding-top: 6px; font-size: 9px; color: #999; }
        footer table { width: 100%; border: none; } footer td { border: none; padding: 0; }
        .page-number:before { content: "Page " counter(page) " of " counter(pages); }

        h1 { font-size: 18px; margin: 0 0 2px 0; }
        .subtitle { color: #666; font-size: 12px; margin-bottom: 16px; }

        .balance { font-size: 16px; font-weight: bold; color: #14532d; margin-bottom: 16px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #e2e2e2; padding: 6px 8px; text-align: left; }
        table.data th { background: #f5f7f5; font-size: 9px; text-transform: uppercase; color: #555; }
        table.data tbody tr:nth-child(even) { background: #fafafa; }
        .text-end { text-align: right; }
        .deposit { color: #0f5132; } .withdrawal { color: #842029; }
        .empty { color: #999; font-style: italic; padding: 14px 0; }
    </style>
</head>
<body>
    <header>
        <table><tr>
            <td><span class="brand">LUTTODA</span><br><span class="brand-sub">Member Savings Ledger</span></td>
            <td class="meta">Generated: {{ now()->format('M d, Y h:i A') }}</td>
        </tr></table>
    </header>
    <footer>
        <table><tr><td>LUTTODA — Savings Ledger · {{ $member->full_name }}</td><td class="text-end page-number"></td></tr></table>
    </footer>

    <h1>Savings Ledger</h1>
    <div class="subtitle">{{ $member->full_name }} · {{ $member->member_no }} · {{ $member->route }} route</div>
    <div class="balance">Current savings balance: ₱{{ number_format($member->savings_balance, 2) }}</div>

    @if ($ledger->count())
        <table class="data">
            <thead>
                <tr>
                    <th style="width:14%">Date</th><th>Source</th><th style="width:12%">Type</th>
                    <th class="text-end" style="width:15%">Amount</th>
                    <th class="text-end" style="width:16%">Running Balance</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ledger as $entry)
                    <tr>
                        <td>{{ $entry->date->format('M d, Y') }}</td>
                        <td>{{ \Illuminate\Support\Str::of($entry->source_type)->replace('_', ' ')->title() }}</td>
                        <td class="{{ $entry->txn_type }}">{{ ucfirst($entry->txn_type) }}</td>
                        <td class="text-end">₱{{ number_format($entry->amount, 2) }}</td>
                        <td class="text-end">₱{{ number_format($entry->running_balance, 2) }}</td>
                        <td>{{ $entry->remarks }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="empty">No ledger entries yet.</p>
    @endif
</body>
</html>
