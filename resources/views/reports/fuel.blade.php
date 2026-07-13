@extends('layouts.app')

@section('title', 'Fuel Consumption Report')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Fuel Consumption Report</h1>
        <a href="{{ route('reports.export', ['type' => 'fuel', 'date' => $to]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export PDF
        </a>
    </div>

    {{-- Date range filter --}}
    <form method="GET" action="{{ route('reports.fuel') }}" class="flex flex-wrap items-end gap-3 mb-6 bg-white p-4 rounded-lg shadow-sm">
        <div>
            <label for="from" class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">From</label>
            <input type="date" name="from" id="from" value="{{ $from }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>
        <div>
            <label for="to" class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">To</label>
            <input type="date" name="to" id="to" value="{{ $to }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>
        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition">
            Filter
        </button>
    </form>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-b border-gray-200">
            <span class="font-semibold text-gray-800">
                Records from {{ \Carbon\Carbon::parse($from)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}
            </span>
            <span class="text-xs font-medium text-gray-500 bg-gray-200 px-2 py-1 rounded-full">{{ $records->count() }} entries</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3">Vehicle</th>
                        <th class="px-4 py-3">Liters</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($records as $i => $record)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($record->consumption_date)->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $record->member->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $record->vehicle_no ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $record->liters ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">₱{{ number_format($record->amount ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">No fuel records found for this range.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($records->count())
                    <tfoot class="bg-gray-50">
                        <tr>
                            <th colspan="5" class="px-4 py-3 text-right text-gray-600">Total</th>
                            <th class="px-4 py-3 text-right text-gray-800">₱{{ number_format($records->sum('amount'), 2) }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
