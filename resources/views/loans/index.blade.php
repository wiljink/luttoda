@extends('layouts.app')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Loans</h1>
    <a href="{{ route('loans.create') }}" class="mt-4 md:mt-0 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow transition duration-150">
        + New Loan Application
    </a>
</div>

@if (session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
        {{ $errors->first() }}
    </div>
@endif

<div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
    <form method="GET" action="{{ route('loans.index') }}" class="flex flex-col md:flex-row gap-4 md:items-end">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
            <select name="status" class="rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                <option value="">All</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div>
            <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded font-semibold shadow-sm transition">
                Filter
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Member</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Loan Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Balance</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($loans as $loan)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-800">
                        {{ $loan->member->firstname }} {{ $loan->member->lastname }}
                        <div class="text-xs text-gray-400">{{ $loan->member->member_no }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ \Illuminate\Support\Carbon::parse($loan->loan_date)->format('M d, Y') }}</td>
                    <td class="px-4 py-3 text-sm font-semibold text-gray-800">₱{{ number_format($loan->amount, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">₱{{ number_format($loan->balance, 2) }}</td>
                    <td class="px-4 py-3 text-sm">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'approved' => 'bg-blue-100 text-blue-700',
                                'active' => 'bg-indigo-100 text-indigo-700',
                                'paid' => 'bg-green-100 text-green-700',
                                'rejected' => 'bg-red-100 text-red-700',
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusColors[$loan->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ ucfirst($loan->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right text-sm space-x-2">
                        <a href="{{ route('loans.show', $loan) }}" class="text-blue-600 hover:underline">View</a>
                        @if (!$loan->payments()->exists())
                            <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="inline" onsubmit="return confirm('Delete this loan?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-400">No loans found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $loans->appends(request()->query())->links() }}
</div>
@endsection
