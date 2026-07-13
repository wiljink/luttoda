@extends('layouts.app')

@section('title', 'Monthly Report')

@section('content')
<div class="max-w-6xl mx-auto">

    <h1 class="text-2xl font-bold text-gray-800 mb-6">Monthly Report</h1>

    {{-- Month filter --}}
    <form method="GET" action="{{ route('reports.monthly') }}" class="flex flex-wrap items-end gap-3 mb-6 bg-white p-4 rounded-lg shadow-sm">
        <div>
            <label for="month" class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Month</label>
            <input type="month" name="month" id="month" value="{{ $month }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>
        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition">
            Filter
        </button>
    </form>

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total Income</p>
            <p class="text-xl font-bold text-green-600">₱{{ number_format($income, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total Expense</p>
            <p class="text-xl font-bold text-red-600">₱{{ number_format($expense, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Net</p>
            <p class="text-xl font-bold {{ ($income - $expense) >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                ₱{{ number_format($income - $expense, 2) }}
            </p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border-l-4 border-slate-500 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total Dues Collected</p>
            <p class="text-xl font-bold text-slate-700">₱{{ number_format($dues->sum('amount'), 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 border-b border-gray-200 font-semibold text-gray-800">
            Daily Dues for {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3">Route</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($dues as $i => $due)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($due->collection_date)->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $due->member->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $due->route ?: '-' }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">₱{{ number_format($due->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-400">No dues recorded for this month.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($dues->count())
                    <tfoot class="bg-gray-50">
                        <tr>
                            <th colspan="4" class="px-4 py-3 text-right text-gray-600">Total</th>
                            <th class="px-4 py-3 text-right text-gray-800">₱{{ number_format($dues->sum('amount'), 2) }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
