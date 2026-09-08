@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold text-gray-800 mb-2">Collections / Rental Income</h1>
    <p class="text-sm text-gray-500 mb-6">
        All recorded income (rental, dispatcher fee, parking fee, tricab rental, other collections)
        for the period, grouped by type.
    </p>

    <div class="space-y-6">

        <form method="GET" class="bg-white p-4 shadow-sm rounded-lg flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">From</label>
                <input type="date" name="from" value="{{ $from }}" class="mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">To</label>
                <input type="date" name="to" value="{{ $to }}" class="mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-md text-sm">Filter</button>

            <div class="flex gap-2 ml-auto text-sm">
                <a href="{{ route('reports.collections-income.page', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]) }}"
                   class="px-3 py-2 rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50">This month</a>
                <a href="{{ route('reports.collections-income.page', ['from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()]) }}"
                   class="px-3 py-2 rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50">This year</a>
            </div>
        </form>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Total collected ({{ \Illuminate\Support\Carbon::parse($from)->format('M j') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('M j, Y') }})</p>
                <p class="text-2xl font-semibold text-emerald-700">₱{{ number_format($grand_total, 2) }}</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Transactions</p>
                <p class="text-2xl font-semibold text-gray-800">{{ $transaction_count }}</p>
            </div>
        </div>

        {{-- Per-category breakdown --}}
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-700">By type</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Type</th>
                        <th class="px-4 py-2 text-right">Entries</th>
                        <th class="px-4 py-2 text-right">Subtotal</th>
                        <th class="px-4 py-2 text-right">Share</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($by_category as $row)
                        <tr>
                            <td class="px-4 py-2 text-gray-800">{{ $row['label'] ?: '—' }}</td>
                            <td class="px-4 py-2 text-right text-gray-500">{{ $row['count'] }}</td>
                            <td class="px-4 py-2 text-right font-medium">₱{{ number_format($row['total'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-500">
                                {{ $grand_total > 0 ? number_format($row['total'] / $grand_total * 100, 1) : '0.0' }}%
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No income recorded for this period.</td></tr>
                    @endforelse
                </tbody>
                @if (count($by_category))
                    <tfoot class="bg-gray-50 font-semibold">
                        <tr>
                            <td class="px-4 py-2">Total</td>
                            <td class="px-4 py-2 text-right">{{ $transaction_count }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($grand_total, 2) }}</td>
                            <td class="px-4 py-2 text-right">100%</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Detail --}}
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-700">Detail</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-left">Type</th>
                        <th class="px-4 py-2 text-left">Description</th>
                        <th class="px-4 py-2 text-left">Ref #</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($transactions as $txn)
                        <tr>
                            <td class="px-4 py-2 text-gray-600">{{ $txn->transaction_date->format('M d, Y') }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ \Illuminate\Support\Str::of((string) $txn->category)->replace('_', ' ')->title() }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $txn->description }}</td>
                            <td class="px-4 py-2 text-gray-400">{{ $txn->reference_no ?: '—' }}</td>
                            <td class="px-4 py-2 text-right font-medium text-emerald-700">₱{{ number_format($txn->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No transactions.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
