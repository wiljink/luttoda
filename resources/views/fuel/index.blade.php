@extends('layouts.app')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Fuel Consumption Records</h1>
    <a href="{{ route('fuel.create') }}" class="mt-4 md:mt-0 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow transition duration-150">
        + Add Fuel Record
    </a>
</div>

@if (session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
        {{ session('success') }}
    </div>
@endif

<div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
    <form method="GET" action="{{ route('fuel.index') }}" class="flex flex-col md:flex-row gap-4 md:items-end">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
        </div>
        <div>
            <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded font-semibold shadow-sm transition">
                Filter
            </button>
        </div>
        @if (request('from') || request('to'))
            <div>
                <a href="{{ route('fuel.index') }}" class="text-sm text-gray-500 hover:underline">Clear filter</a>
            </div>
        @endif
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Member</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Liters</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Refill Station</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($records as $record)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-600">{{ \Illuminate\Support\Carbon::parse($record->consumption_date)->format('M d, Y') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-800">
                        {{ $record->member->firstname }} {{ $record->member->lastname }}
                        <div class="text-xs text-gray-400">{{ $record->member->member_no }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($record->liters, 2) }} L</td>
                    <td class="px-4 py-3 text-sm font-semibold text-gray-800">₱{{ number_format($record->amount, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $record->refill_station ?: '—' }}</td>
                    <td class="px-4 py-3 text-right text-sm space-x-2">
                        <a href="{{ route('fuel.show', $record) }}" class="text-blue-600 hover:underline">View</a>
                        <a href="{{ route('fuel.edit', $record) }}" class="text-amber-600 hover:underline">Edit</a>
                        <form action="{{ route('fuel.destroy', $record) }}" method="POST" class="inline" onsubmit="return confirm('Delete this fuel record?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-400">No fuel records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $records->appends(request()->query())->links() }}
</div>
@endsection
