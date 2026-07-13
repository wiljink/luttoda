@extends('layouts.app')

@section('title', 'Daily Report')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Daily Collection Report</h1>
        <a href="{{ route('reports.export', ['type' => 'daily', 'date' => $date]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export PDF
        </a>
    </div>

    {{-- Date filter --}}
    <form method="GET" action="{{ route('reports.daily') }}" class="flex flex-wrap items-end gap-3 mb-6 bg-white p-4 rounded-lg shadow-sm">
        <div>
            <label for="date" class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Date</label>
            <input type="date" name="date" id="date" value="{{ $date }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>
        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition">
            Filter
        </button>
    </form>

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total Income</p>
            <p class="text-2xl font-bold text-green-600">₱{{ number_format($income, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total Expense</p>
            <p class="text-2xl font-bold text-red-600">₱{{ number_format($expense, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Net</p>
            <p class="text-2xl font-bold {{ ($income - $expense) >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                ₱{{ number_format($income - $expense, 2) }}
            </p>
        </div>
    </div>

    {{-- Dues grouped by route --}}
    @forelse($dues as $route => $routeDues)
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-b border-gray-200">
                <span class="font-semibold text-gray-800">Route: {{ $route ?: 'Unassigned' }}</span>
                <span class="text-xs font-medium text-gray-500 bg-gray-200 px-2 py-1 rounded-full">
                    {{ $routeDues->count() }} {{ Str::plural('entry', $routeDues->count()) }}
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Member</th>
                            <th class="px-4 py-3">Member No.</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($routeDues as $i => $due)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-700">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 text-gray-800">{{ $due->member->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $due->member->member_no ?? '-' }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-800">₱{{ number_format($due->amount, 2) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $due->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($due->status ?? 'pending') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <th colspan="3" class="px-4 py-3 text-right text-gray-600">Route Subtotal</th>
                            <th class="px-4 py-3 text-right text-gray-800">₱{{ number_format($routeDues->sum('amount'), 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-blue-50 border-l-4 border-blue-400 text-blue-700 p-4 rounded shadow-sm">
            No dues recorded for {{ $date }}.
        </div>
    @endforelse

</div>
@endsection
