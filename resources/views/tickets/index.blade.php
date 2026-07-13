@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Tickets</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $availableCount }} available &middot; {{ $usedCount }} used
        </p>
    </div>
    <a href="{{ route('tickets.create') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow transition text-sm">
        + Add Ticket Booklet
    </a>
</div>

@if (session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm p-3 rounded">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded">
        {{ session('error') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
            <tr>
                <th class="px-4 py-3">Ticket Range</th>
                <th class="px-4 py-3">Route</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Count</th>
                <th class="px-4 py-3">Last Used</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($ranges as $range)
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-800">
                        @if ($range['from'] === $range['to'])
                            {{ $range['from'] }}
                        @else
                            {{ $range['from'] }} – {{ $range['to'] }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $range['route'] ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if ($range['status'] === 'available')
                            <span class="inline-block bg-green-50 text-green-700 text-xs font-semibold px-2 py-1 rounded">Available</span>
                        @else
                            <span class="inline-block bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">Used</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $range['count'] }}</td>
                    <td class="px-4 py-3 text-gray-500">
                        {{ $range['used_on']?->format('M d, Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('tickets.editRange', [
                                    'ticket_ids' => implode(',', $range['ids']),
                                    'from' => $range['from'],
                                    'to' => $range['to'],
                                    'route' => $range['route'],
                                    'count' => $range['count'],
                                ]) }}" class="text-blue-600 hover:underline text-xs font-semibold">
                                Edit Range
                            </a>

                            @if ($range['status'] === 'available')
                                <form action="{{ route('tickets.bulkDestroy') }}" method="POST"
                                      onsubmit="return confirm('Delete all {{ $range['count'] }} ticket(s) in range {{ $range['from'] }}–{{ $range['to'] }}? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="ticket_ids" value="{{ implode(',', $range['ids']) }}">
                                    <button type="submit" class="text-red-600 hover:underline text-xs font-semibold">
                                        Delete Range
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-gray-300">Locked (used)</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">No tickets found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
