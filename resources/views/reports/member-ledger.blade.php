@extends('layouts.app')

@section('title', 'Member Ledger')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-1">Member Ledger</h1>
            <p class="text-sm text-gray-500">
                {{ $member->name }}
                @if($member->member_no) &mdash; No. {{ $member->member_no }} @endif
                @if($member->contact) &mdash; {{ $member->contact }} @endif
            </p>
        </div>
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print
        </button>
    </div>

    {{-- Daily Dues --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="px-4 py-3 bg-slate-50 border-b border-gray-200 font-semibold text-gray-800">Daily Dues</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Route</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($dues as $i => $due)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($due->collection_date)->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $due->route ?: '-' }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">₱{{ number_format($due->amount, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $due->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($due->status ?? 'pending') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No dues on record.</td></tr>
                    @endforelse
                </tbody>
                @if($dues->count())
                    <tfoot class="bg-gray-50">
                        <tr>
                            <th colspan="3" class="px-4 py-3 text-right text-gray-600">Total</th>
                            <th class="px-4 py-3 text-right text-gray-800">₱{{ number_format($dues->sum('amount'), 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Loans --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="px-4 py-3 bg-slate-50 border-b border-gray-200 font-semibold text-gray-800">Loans</div>
        @forelse($loans as $loan)
            <div class="border-b border-gray-100 last:border-0 p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-800">Loan #{{ $loan->id }}</span>
                        <span class="text-xs font-medium text-gray-500 bg-gray-200 px-2 py-1 rounded-full">
                            {{ ucfirst($loan->status ?? 'active') }}
                        </span>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-800">Principal: ₱{{ number_format($loan->amount ?? 0, 2) }}</div>
                        <div class="text-xs text-gray-500">Balance: ₱{{ number_format($loan->balance ?? 0, 2) }}</div>
                    </div>
                </div>

                @if($loan->payments->count())
                    <div class="overflow-x-auto rounded-lg border border-gray-100">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="px-3 py-2">Date</th>
                                    <th class="px-3 py-2 text-right">Payment</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($loan->payments as $payment)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-600">{{ \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') }}</td>
                                        <td class="px-3 py-2 text-right font-medium text-gray-800">₱{{ number_format($payment->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-xs text-gray-400">No payments recorded yet.</p>
                @endif
            </div>
        @empty
            <p class="p-4 text-center text-gray-400">No loans on record.</p>
        @endforelse
    </div>

    {{-- Benefits --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="px-4 py-3 bg-slate-50 border-b border-gray-200 font-semibold text-gray-800">Benefits</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($benefits as $i => $benefit)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $benefit->type ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ optional($benefit->date ?? $benefit->created_at)->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">₱{{ number_format($benefit->amount ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No benefits on record.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Fuel Consumption --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="px-4 py-3 bg-slate-50 border-b border-gray-200 font-semibold text-gray-800">Fuel Consumption</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Liters</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($fuel as $i => $record)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($record->consumption_date)->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $record->liters ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">₱{{ number_format($record->amount ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No fuel records on record.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
