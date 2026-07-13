@extends('layouts.app')

@section('content')
@php
    $totalContributions = $member->dailyDues->sum('amount_paid');
    $totalTicketsBought = $member->dailyDues->sum('ticket_quantity');
    $totalDuesRecorded = $member->dailyDues->count();
    $totalSavingsShare = $member->dailyDues->sum('savings_share');
    $totalRebateShare = $member->dailyDues->sum('rebate_share');
    $totalAssociationShare = $member->dailyDues->sum('association_share');
@endphp

<div class="mb-6">
    <a href="{{ route('members.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Members</a>
    <div class="flex items-center justify-between mt-2">
        <h1 class="text-2xl font-bold text-gray-800">
            {{ $member->firstname }} {{ $member->middlename ? $member->middlename[0].'. ' : '' }}{{ $member->lastname }}
        </h1>
        <span class="text-xs font-semibold px-3 py-1 rounded-full {{ $member->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
            {{ ucfirst($member->status) }}
        </span>
    </div>
    <p class="text-sm text-gray-500 mt-1">
        {{ $member->member_no }} · {{ $member->plate_number }} · {{ $member->route }} route
    </p>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total Contributions</p>
        <p class="text-xl font-bold text-gray-800 mt-1">₱{{ number_format($totalContributions, 2) }}</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Savings Balance</p>
        <p class="text-xl font-bold text-green-700 mt-1">₱{{ number_format($member->savings_balance, 2) }}</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Tickets Bought</p>
        <p class="text-xl font-bold text-gray-800 mt-1">{{ $totalTicketsBought }}</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Dues Recorded</p>
        <p class="text-xl font-bold text-gray-800 mt-1">{{ $totalDuesRecorded }}</p>
    </div>
</div>

{{-- Contribution breakdown --}}
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Contribution Breakdown</h2>
    <div class="grid grid-cols-3 gap-4 text-sm">
        <div>
            <p class="text-gray-500">Savings Share</p>
            <p class="font-semibold text-gray-800">₱{{ number_format($totalSavingsShare, 2) }}</p>
        </div>
        <div>
            <p class="text-gray-500">Rebate Share</p>
            <p class="font-semibold text-gray-800">₱{{ number_format($totalRebateShare, 2) }}</p>
        </div>
        <div>
            <p class="text-gray-500">Association Share</p>
            <p class="font-semibold text-gray-800">₱{{ number_format($totalAssociationShare, 2) }}</p>
        </div>
    </div>
</div>

{{-- Member details --}}
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Member Information</h2>
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <p class="text-gray-500">Operator Name</p>
            <p class="font-semibold text-gray-800">{{ $member->operator_name }}</p>
        </div>
        <div>
            <p class="text-gray-500">Contact Number</p>
            <p class="font-semibold text-gray-800">{{ $member->contact_number ?? '—' }}</p>
        </div>
        <div>
            <p class="text-gray-500">Address</p>
            <p class="font-semibold text-gray-800">{{ $member->address ?? '—' }}</p>
        </div>
        <div>
            <p class="text-gray-500">Date Joined</p>
            <p class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($member->date_joined)->format('M d, Y') }}</p>
        </div>
    </div>
</div>

{{-- Daily dues history --}}
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Daily Dues History</h2>

    @if($member->dailyDues->isEmpty())
        <p class="text-sm text-gray-400">No dues recorded yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-200">
                    <tr>
                        <th class="py-2 pr-4">Date</th>
                        <th class="py-2 pr-4">Route</th>
                        <th class="py-2 pr-4">Ticket #</th>
                        <th class="py-2 pr-4">Qty</th>
                        <th class="py-2 pr-4">Amount</th>
                        <th class="py-2 pr-4">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($member->dailyDues->sortByDesc('collection_date') as $due)
                        <tr class="border-b border-gray-100">
                            <td class="py-2 pr-4">{{ $due->collection_date->format('M d, Y') }}</td>
                            <td class="py-2 pr-4">{{ $due->route }}</td>
                            <td class="py-2 pr-4">#{{ $due->ticket_number }}</td>
                            <td class="py-2 pr-4">{{ $due->ticket_quantity ?? 1 }}</td>
                            <td class="py-2 pr-4 font-semibold">₱{{ number_format($due->amount_paid, 2) }}</td>
                            <td class="py-2 pr-4 text-gray-500">{{ $due->remarks ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 font-semibold">
                        <td class="py-2 pr-4" colspan="3">Total</td>
                        <td class="py-2 pr-4">{{ $totalTicketsBought }}</td>
                        <td class="py-2 pr-4">₱{{ number_format($totalContributions, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>

{{-- Loans --}}
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Loans</h2>

    @if($member->loans->isEmpty())
        <p class="text-sm text-gray-400">No loans on record.</p>
    @else
        <div class="space-y-3">
            @foreach($member->loans as $loan)
                @php
                    $totalPaid = $loan->payments->sum('amount');
                    $balance = $loan->amount - $totalPaid;
                @endphp
                <div class="border border-gray-100 rounded p-3">
                    <div class="flex justify-between text-sm">
                        <p class="font-semibold text-gray-800">Loan #{{ $loan->id }} — ₱{{ number_format($loan->amount, 2) }}</p>
                        <p class="text-gray-500">Balance: ₱{{ number_format($balance, 2) }}</p>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">{{ $loan->payments->count() }} payment(s) made — ₱{{ number_format($totalPaid, 2) }} paid</p>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Benefits --}}
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Benefits</h2>

    @if($member->benefits->isEmpty())
        <p class="text-sm text-gray-400">No benefits on record.</p>
    @else
        <ul class="text-sm divide-y divide-gray-100">
            @foreach($member->benefits as $benefit)
                <li class="py-2 flex justify-between">
                    <span class="text-gray-700">{{ $benefit->type ?? $benefit->description ?? 'Benefit #'.$benefit->id }}</span>
                    <span class="font-semibold text-gray-800">₱{{ number_format($benefit->amount ?? 0, 2) }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>

{{-- Fuel consumption --}}
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Fuel Consumption</h2>

    @if($member->fuelConsumptions->isEmpty())
        <p class="text-sm text-gray-400">No fuel consumption records yet.</p>
    @else
        <ul class="text-sm divide-y divide-gray-100">
            @foreach($member->fuelConsumptions as $fuel)
                <li class="py-2 flex justify-between">
                    <span class="text-gray-700">{{ optional($fuel->date)->format('M d, Y') ?? $fuel->created_at->format('M d, Y') }}</span>
                    <span class="font-semibold text-gray-800">{{ $fuel->liters ?? '' }} L — ₱{{ number_format($fuel->amount ?? 0, 2) }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>

<div class="flex justify-end gap-3 mt-6">
    <a href="{{ route('members.edit', $member) }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 px-4 rounded shadow">Edit Member</a>
</div>
@endsection
