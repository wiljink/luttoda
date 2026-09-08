@extends('layouts.app')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Daily Dues Collection</h1>
    <a href="{{ route('daily-dues.create') }}" class="mt-4 md:mt-0 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow transition duration-150">
        + Record Collection
    </a>
</div>

@if (session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
        {{ session('success') }}
    </div>
@endif

<div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
    <form method="GET" action="{{ route('daily-dues.index') }}" class="flex flex-col md:flex-row gap-4 md:items-end">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
            <input type="date" name="date" value="{{ $date }}" class="rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Route</label>
            <select name="route" class="rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                <option value="">All Routes</option>
                <option value="Carmen" {{ request('route') === 'Carmen' ? 'selected' : '' }}>Carmen</option>
                <option value="Cogon" {{ request('route') === 'Cogon' ? 'selected' : '' }}>Cogon</option>
            </select>
        </div>
        <div>
            <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded font-semibold shadow-sm transition">
                Filter
            </button>
        </div>
    </form>
</div>

<div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6 flex items-center justify-between">
    <span class="text-gray-600 font-medium">
        Total Collected on {{ \Illuminate\Support\Carbon::parse($date)->format('M d, Y') }}
        @unless (\Illuminate\Support\Carbon::parse($date)->isToday())
            <span class="ml-2 text-xs text-amber-600">(most recent day with collections — not today)</span>
        @endunless
    </span>
    <span class="text-2xl font-bold text-green-600">₱{{ number_format($totalToday, 2) }}</span>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Member</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Route</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ticket #</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Amount Paid</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Collected By</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($dues as $due)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-800">
                        {{ $due->member->firstname }} {{ $due->member->lastname }}
                        <div class="text-xs text-gray-400">{{ $due->member->member_no }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $due->route }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $due->ticket_number ?: '—' }}</td>
                    <td class="px-4 py-3 text-sm font-semibold text-gray-800">₱{{ number_format($due->amount_paid, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $due->collector->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right text-sm space-x-2">
                        <a href="{{ route('daily-dues.show', $due) }}" class="text-blue-600 hover:underline">View</a>
                        <a href="{{ route('daily-dues.edit', $due) }}" class="text-amber-600 hover:underline">Edit</a>
                        <form action="{{ route('daily-dues.destroy', $due) }}" method="POST" class="inline" onsubmit="return confirm('Delete this record? This will adjust the savings balance.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-400">No collections recorded for this date.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $dues->appends(request()->query())->links() }}
</div>
@endsection
