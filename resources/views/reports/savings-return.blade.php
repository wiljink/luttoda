@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold text-gray-800 mb-2">Annual Savings Return</h1>
    <p class="text-sm text-gray-500 mb-6">
        Of every ₱50 daily due, <strong>₱35 (savings) + ₱7.50 (member's share)</strong> is paid back
        in cash to the member once a year, on <strong>{{ $returnDate->format('F j, Y') }}</strong>.
        The remaining ₱7.50 per ticket is association income.
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
            <p class="text-sm text-gray-500">Total Pending Return ({{ $year }})</p>
            <p class="text-2xl font-semibold text-yellow-700">₱{{ number_format($totalPending, 2) }}</p>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Member</th>
                        <th class="px-4 py-2 text-right">Savings (₱35)</th>
                        <th class="px-4 py-2 text-right">Share (₱7.50)</th>
                        <th class="px-4 py-2 text-right">Entitlement</th>
                        <th class="px-4 py-2 text-right">Returned</th>
                        <th class="px-4 py-2 text-right">Pending</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($members as $row)
                        <tr>
                            <td class="px-4 py-2">{{ $row['member_name'] ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($row['savings'], 2) }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($row['share'], 2) }}</td>
                            <td class="px-4 py-2 text-right font-medium">₱{{ number_format($row['entitlement'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-green-700">₱{{ number_format($row['returned'], 2) }}</td>
                            <td class="px-4 py-2 text-right {{ $row['pending'] > 0 ? 'text-yellow-700 font-medium' : 'text-gray-400' }}">
                                ₱{{ number_format($row['pending'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">No daily dues recorded for {{ $year }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($totalPending > 0)
            <form method="POST" action="{{ route('reports.savings-return.release', $year) }}"
                  onsubmit="return confirm('Pay out ₱{{ number_format($totalPending, 2) }} in annual savings return for {{ $year }} (dated {{ $returnDate->format('M j, Y') }})? This reduces member savings balances and cannot be undone.');">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-700 text-white rounded-md text-sm">
                    Release Savings Return for {{ $year }}
                </button>
            </form>
        @endif
    </div>
@endsection
