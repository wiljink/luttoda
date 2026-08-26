@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Savings Ledger — {{ $member->full_name }}</h1>

    <div class="space-y-6">

        <div class="bg-white p-4 shadow-sm rounded-lg flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ $member->member_no }} — {{ $member->route }}</p>
                <p class="text-2xl font-semibold text-gray-800">₱{{ number_format($member->savings_balance, 2) }}</p>
                <p class="text-xs text-gray-400">Current savings balance</p>
            </div>
            <a href="{{ route('reports.member.export', $member) }}"
               class="px-4 py-2 bg-slate-800 text-white rounded-md text-sm">
                Export PDF
            </a>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-left">Source</th>
                        <th class="px-4 py-2 text-left">Type</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                        <th class="px-4 py-2 text-right">Running Balance</th>
                        <th class="px-4 py-2 text-left">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($ledger as $entry)
                        <tr>
                            <td class="px-4 py-2">{{ $entry->date->format('M d, Y') }}</td>
                            <td class="px-4 py-2">{{ str($entry->source_type)->replace('_', ' ')->title() }}</td>
                            <td class="px-4 py-2">
                                <span class="{{ $entry->txn_type === 'deposit' ? 'text-green-700' : 'text-red-700' }}">
                                    {{ ucfirst($entry->txn_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($entry->amount, 2) }}</td>
                            <td class="px-4 py-2 text-right">₱{{ number_format($entry->running_balance, 2) }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $entry->remarks }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">No ledger entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <h3 class="px-4 py-2 font-medium bg-gray-50 border-b">Loans</h3>
                <div class="divide-y divide-gray-100">
                    @forelse ($loans as $loan)
                        <div class="px-4 py-2 flex justify-between text-sm">
                            <span>{{ $loan->loan_date->format('M d, Y') }} — {{ ucfirst($loan->status) }}</span>
                            <span>₱{{ number_format($loan->amount, 2) }} (bal. ₱{{ number_format($loan->balance, 2) }})</span>
                        </div>
                    @empty
                        <p class="px-4 py-4 text-sm text-gray-500">No loans on record.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <h3 class="px-4 py-2 font-medium bg-gray-50 border-b">Benefits</h3>
                <div class="divide-y divide-gray-100">
                    @forelse ($benefits as $benefit)
                        <div class="px-4 py-2 flex justify-between text-sm">
                            <span>{{ ucfirst($benefit->benefit_type) }} — {{ ucfirst($benefit->status) }}</span>
                            <span>₱{{ number_format($benefit->amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="px-4 py-4 text-sm text-gray-500">No benefit claims on record.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
