@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('loans.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Loans</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Loan Details</h1>
</div>

@if (session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded max-w-2xl">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded max-w-2xl">
        {{ $errors->first() }}
    </div>
@endif

<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 max-w-2xl space-y-6 mb-6">

    <div class="flex items-center justify-between">
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Member</div>
            <div class="text-gray-800 font-medium text-lg">{{ $loan->member->firstname }} {{ $loan->member->lastname }}</div>
            <div class="text-sm text-gray-500">{{ $loan->member->member_no }} · {{ $loan->member->plate_number }}</div>
        </div>
        @php
            $statusColors = [
                'pending' => 'bg-yellow-100 text-yellow-700',
                'approved' => 'bg-blue-100 text-blue-700',
                'active' => 'bg-indigo-100 text-indigo-700',
                'paid' => 'bg-green-100 text-green-700',
                'rejected' => 'bg-red-100 text-red-700',
            ];
        @endphp
        <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $statusColors[$loan->status] ?? 'bg-gray-100 text-gray-700' }}">
            {{ ucfirst($loan->status) }}
        </span>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Loan Date</div>
            <div class="text-gray-800 font-medium">{{ \Illuminate\Support\Carbon::parse($loan->loan_date)->format('M d, Y') }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Due Date</div>
            <div class="text-gray-800 font-medium">{{ $loan->due_date ? \Illuminate\Support\Carbon::parse($loan->due_date)->format('M d, Y') : '—' }}</div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6">
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Loan Amount</div>
            <div class="text-gray-800 font-bold text-lg">₱{{ number_format($loan->amount, 2) }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Interest Rate</div>
            <div class="text-gray-800 font-medium">{{ number_format($loan->interest_rate, 2) }}%</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Remaining Balance</div>
            <div class="text-red-600 font-bold text-lg">₱{{ number_format($loan->balance, 2) }}</div>
        </div>
    </div>

    <div>
        <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Purpose</div>
        <div class="text-gray-800">{{ $loan->purpose ?: '—' }}</div>
    </div>

    @if ($loan->status === 'pending')
        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <form action="{{ route('loans.reject', $loan) }}" method="POST" onsubmit="return confirm('Reject this loan application?');">
                @csrf
                <button type="submit" class="px-4 py-2 border border-red-300 text-red-600 rounded font-medium hover:bg-red-50">
                    Reject
                </button>
            </form>
            <form action="{{ route('loans.approve', $loan) }}" method="POST" onsubmit="return confirm('Approve this loan?');">
                @csrf
                <button type="submit" class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-semibold shadow-sm transition">
                    Approve Loan
                </button>
            </form>
        </div>
    @endif
</div>

@if (in_array($loan->status, ['approved', 'active', 'paid']))
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 max-w-2xl mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Record Payment</h2>
        @if ($loan->status !== 'paid')
            <form action="{{ route('loans.payment', $loan) }}" method="POST" class="flex items-end gap-4">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Payment Amount (₱) *</label>
                    <input type="number" step="0.01" min="1" max="{{ $loan->balance }}" name="amount" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                </div>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold shadow-sm transition">
                    Add Payment
                </button>
            </form>
        @else
            <p class="text-sm text-green-600 font-medium">This loan has been fully paid.</p>
        @endif
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm border border-gray-200 max-w-2xl overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-sm">Payment History</div>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Received By</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($loan->payments as $payment)
                <tr>
                    <td class="px-4 py-2 text-sm text-gray-600">{{ \Illuminate\Support\Carbon::parse($payment->payment_date)->format('M d, Y') }}</td>
                    <td class="px-4 py-2 text-sm font-semibold text-gray-800">₱{{ number_format($payment->amount, 2) }}</td>
                    <td class="px-4 py-2 text-sm text-gray-600">{{ $payment->receivedBy->name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-4 py-4 text-center text-sm text-gray-400">No payments recorded yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
