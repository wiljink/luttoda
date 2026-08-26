@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Fuel Consumption Report</h1>

    <div class="space-y-6">

        <form method="GET" class="bg-white p-4 shadow-sm rounded-lg flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">From</label>
                <input type="date" name="from" value="{{ $from }}" class="mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">To</label>
                <input type="date" name="to" value="{{ $to }}" class="mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-md text-sm">Filter</button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Total Liters</p>
                <p class="text-xl font-semibold text-gray-800">{{ number_format($records->sum('liters'), 2) }} L</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Total Rebate</p>
                <p class="text-xl font-semibold text-green-700">₱{{ number_format($records->sum('total_rebate'), 2) }}</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Total Coop Deposit</p>
                <p class="text-xl font-semibold text-gray-800">₱{{ number_format($records->sum('total_coop_deposit'), 2) }}</p>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-left">Member</th>
                        <th class="px-4 py-2 text-left">Station</th>
                        <th class="px-4 py-2 text-right">Liters</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                        <th class="px-4 py-2 text-right">Rebate</th>
                        <th class="px-4 py-2 text-right">Coop Deposit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $record)
                        <tr>
                            <td class="px-4 py-2">{{ $record->consumption_date->format('M d, Y') }}</td>
                            <td class="px-4 py-2">{{ $record->member->full_name ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $record->refill_station ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($record->liters, 2) }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($record->amount, 2) }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($record->total_rebate, 2) }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($record->total_coop_deposit, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">No fuel records for this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
