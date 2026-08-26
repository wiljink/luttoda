@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Association Fund Report</h1>

    <div class="space-y-6">

        <form method="GET" class="bg-white p-4 shadow-sm rounded-lg flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">From</label>
                <input type="date" name="from" value="{{ $start_date }}" class="mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">To</label>
                <input type="date" name="to" value="{{ $end_date }}" class="mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-md text-sm">Filter</button>
        </form>

        <div class="bg-white p-4 shadow-sm rounded-lg">
            <p class="text-sm text-gray-500">Total Association Fund</p>
            <p class="text-2xl font-semibold text-gray-800">₱{{ number_format($total_association_fund, 2) }}</p>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Route</th>
                        <th class="px-4 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($by_route as $row)
                        <tr>
                            <td class="px-4 py-2">{{ $row['route'] }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($row['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-6 text-center text-gray-500">No association fund collected for this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
