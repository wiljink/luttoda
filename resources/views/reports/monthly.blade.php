@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Monthly Report</h1>

    <div class="space-y-6">

        <form method="GET" class="bg-white p-4 shadow-sm rounded-lg flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Month</label>
                <input type="month" name="month" value="{{ $month }}"
                       class="mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-md text-sm">Filter</button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Total Dues Collected</p>
                <p class="text-xl font-semibold text-gray-800">₱{{ number_format($dues->sum('amount_paid'), 2) }}</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Other Income</p>
                <p class="text-xl font-semibold text-green-700">₱{{ number_format($income, 2) }}</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Expenses</p>
                <p class="text-xl font-semibold text-red-700">₱{{ number_format($expense, 2) }}</p>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-left">Route</th>
                        <th class="px-4 py-2 text-right">Tickets</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($dues->sortBy('collection_date') as $due)
                        <tr>
                            <td class="px-4 py-2">{{ $due->collection_date->format('M d, Y') }}</td>
                            <td class="px-4 py-2">{{ $due->route }}</td>
                            <td class="px-4 py-2 text-right">{{ $due->ticket_quantity }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($due->amount_paid, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No dues recorded for this month.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
