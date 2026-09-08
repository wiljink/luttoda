@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold text-gray-800 mb-2">Annual Dividend Rebate</h1>
    <p class="text-sm text-gray-500 mb-6">
        50% of each member's diesel value for the year, returned to their savings:
        <span class="font-medium">(liters &times; diesel price) &divide; 2</span>.
        Released every December. Members and non-members both share; terminated members are excluded.
    </p>

    <div class="space-y-6">

        <form method="GET" class="bg-white p-4 shadow-sm rounded-lg flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Year</label>
                <input type="number" name="year" value="{{ $year }}" min="2020" max="2100"
                       class="mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-md text-sm">Filter</button>
        </form>

        <div class="bg-white p-4 shadow-sm rounded-lg">
            <p class="text-sm text-gray-500">Total Pending Release ({{ $year }})</p>
            <p class="text-2xl font-semibold text-yellow-700">₱{{ number_format($totalPending, 2) }}</p>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Member</th>
                        <th class="px-4 py-2 text-right">Liters</th>
                        <th class="px-4 py-2 text-right">Diesel Price</th>
                        <th class="px-4 py-2 text-right">Dividend</th>
                        <th class="px-4 py-2 text-right">Released</th>
                        <th class="px-4 py-2 text-right">Pending</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($members as $row)
                        <tr>
                            <td class="px-4 py-2">{{ $row['member_name'] ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($row['total_liters'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-500">₱{{ number_format($row['diesel_price'], 2) }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($row['dividend'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-green-700">₱{{ number_format($row['released'], 2) }}</td>
                            <td class="px-4 py-2 text-right {{ $row['pending'] > 0 ? 'text-yellow-700 font-medium' : 'text-gray-400' }}">
                                ₱{{ number_format($row['pending'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">No diesel consumption recorded for {{ $year }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($totalPending > 0)
            <form method="POST" action="{{ route('reports.dividend-rebate.release', $year) }}"
                  onsubmit="return confirm('Release ₱{{ number_format($totalPending, 2) }} in dividend rebate to member savings for {{ $year }}? This cannot be undone.');">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-700 text-white rounded-md text-sm">
                    Release Pending Dividend for {{ $year }}
                </button>
            </form>
        @endif
    </div>
@endsection
