@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Daily Collection Report</h1>

    <div class="space-y-6">

        <form method="GET" class="bg-white p-4 shadow-sm rounded-lg flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Date</label>
                <input type="date" name="date" value="{{ $date }}"
                       class="mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-md text-sm">Filter</button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Total Cash In</p>
                <p class="text-xl font-semibold text-green-700">₱{{ number_format($cashPosition->total_cash_in, 2) }}</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Total Cash Out</p>
                <p class="text-xl font-semibold text-red-700">₱{{ number_format($cashPosition->total_cash_out, 2) }}</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Net Cash Position</p>
                <p class="text-xl font-semibold {{ $cashPosition->net_cash_position >= 0 ? 'text-green-700' : 'text-red-700' }}">
                    ₱{{ number_format($cashPosition->net_cash_position, 2) }}
                </p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Benefits + Loans Released</p>
                <p class="text-xl font-semibold text-gray-800">
                    ₱{{ number_format($cashPosition->benefits_paid + $cashPosition->loans_released, 2) }}
                </p>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Route</th>
                        <th class="px-4 py-2 text-left">Collector</th>
                        <th class="px-4 py-2 text-right">Tickets</th>
                        <th class="px-4 py-2 text-right">Collected</th>
                        <th class="px-4 py-2 text-right">Savings</th>
                        <th class="px-4 py-2 text-right">Rebate Pool</th>
                        <th class="px-4 py-2 text-right">Assoc. Fund</th>
                        <th class="px-4 py-2 text-right">Variance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($collectionReport as $row)
                        <tr>
                            <td class="px-4 py-2">{{ $row->route }}</td>
                            <td class="px-4 py-2">{{ $row->collector_name }}</td>
                            <td class="px-4 py-2 text-right">{{ $row->tickets_collected }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($row->total_collected, 2) }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($row->total_to_savings, 2) }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($row->total_to_rebate_pool, 2) }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($row->total_to_association_fund, 2) }}</td>
                            <td class="px-4 py-2 text-right">
                                @if ($row->variance_count > 0)
                                    <span class="text-red-600 font-medium">{{ $row->variance_count }}</span>
                                @else
                                    <span class="text-gray-400">0</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">No collections recorded for this date.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <a href="{{ route('reports.export', ['type' => 'daily', 'date' => $date]) }}"
           class="inline-block px-4 py-2 bg-slate-800 text-white rounded-md text-sm">
            Export PDF
        </a>
    </div>
@endsection
