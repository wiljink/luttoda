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
    <div class="flex items-center space-x-4 mt-2">
        @if ($member->photo_url)
            <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="w-16 h-16 rounded-full object-cover border border-gray-200 flex-shrink-0">
        @else
            <div class="w-16 h-16 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
            </div>
        @endif
        <div class="flex-1">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ $member->firstname }} {{ $member->middlename ? $member->middlename[0].'. ' : '' }}{{ $member->lastname }}
                </h1>
                <div class="flex items-center gap-2">
                    @if ($member->category === 'non-member')
                        <span class="text-xs font-semibold px-3 py-1 rounded-full bg-amber-100 text-amber-700">Non-member</span>
                    @endif
                    @php
                        $statusStyle = match ($member->status) {
                            'active' => 'bg-green-100 text-green-700',
                            'terminated' => 'bg-red-100 text-red-700',
                            'suspended' => 'bg-orange-100 text-orange-700',
                            default => 'bg-gray-200 text-gray-600',
                        };
                    @endphp
                    <span class="text-xs font-semibold px-3 py-1 rounded-full {{ $statusStyle }}">
                        {{ ucfirst($member->status) }}
                    </span>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                {{ $member->member_no }} · {{ $member->plate_number }} · {{ $member->route }} route
            </p>
        </div>
    </div>

    @php
        $sanction = $member->currentSanction;
        $sanctionLabels = ['suspension' => 'Suspended', 'termination' => 'Terminated', 'dismembership' => 'Dismembered'];
    @endphp
    @if ($sanction)
        <div class="mt-4 bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded shadow-sm">
            <p class="font-bold">
                Member under sanction — {{ $sanctionLabels[$sanction->sanction] ?? ucfirst($sanction->sanction) }}
            </p>
            <p class="text-sm mt-1">
                From violation on {{ $sanction->violation_date->format('M j, Y') }}: {{ $sanction->type }}
                @if ($sanction->sanction === 'suspension' && $sanction->sanction_until)
                    · reinstates {{ $sanction->sanction_until->format('M j, Y') }}
                @endif
            </p>
            @if ($sanction->notes)
                <p class="text-sm mt-1 text-red-700">{{ $sanction->notes }}</p>
            @endif
            <a href="{{ route('members.violations.index', $member) }}" class="text-sm font-medium underline mt-1 inline-block">View violations</a>
        </div>
    @endif
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

{{-- Savings deposit breakdown --}}
@php
    $depositMap = collect($savingsBreakdown['items'])->keyBy('key');
    $fromDues = $depositMap['daily_dues']['amount'] ?? 0;
    $fromFuel = $depositMap['fuel_rebate']['amount'] ?? 0;
    $reconciles = abs($savingsBreakdown['total'] - (float) $member->savings_balance) < 0.01;
@endphp
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Savings Deposit Breakdown</h2>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">From Daily Dues</p>
            <p class="text-xl font-bold text-green-700 mt-1">₱{{ number_format($fromDues, 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">From Fuel Rebate</p>
            <p class="text-xl font-bold text-green-700 mt-1">₱{{ number_format($fromFuel, 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-[#1B3A4B]/5 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Savings Balance</p>
            <p class="text-xl font-bold text-[#1B3A4B] mt-1">₱{{ number_format($member->savings_balance, 2) }}</p>
        </div>
    </div>

    @if (count($savingsBreakdown['items']))
        <table class="min-w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach ($savingsBreakdown['items'] as $item)
                    <tr>
                        <td class="py-2 text-gray-600">{{ $item['label'] }}</td>
                        <td class="py-2 text-right font-medium {{ $item['amount'] < 0 ? 'text-rose-600' : 'text-gray-800' }}">
                            {{ $item['amount'] < 0 ? '−' : '+' }}₱{{ number_format(abs($item['amount']), 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 font-semibold">
                    <td class="py-2 text-gray-800">Total deposited to savings</td>
                    <td class="py-2 text-right text-gray-900">₱{{ number_format($savingsBreakdown['total'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
        @unless ($reconciles)
            <p class="mt-3 text-xs text-amber-600">
                Note: ledger total differs from the stored savings balance
                (₱{{ number_format($member->savings_balance, 2) }}) by
                ₱{{ number_format(abs($savingsBreakdown['total'] - (float) $member->savings_balance), 2) }}.
            </p>
        @endunless
    @else
        <p class="text-sm text-gray-400">No savings deposits recorded yet.</p>
    @endif
</div>

{{-- Alkansiya Program (voluntary SSS savings) --}}
@php
    $alkansiyaContribs = $member->alkansiyaContributions->sortByDesc('contribution_date');
@endphp
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Alkansiya Program <span class="text-xs font-normal text-gray-400">(voluntary SSS savings — separate from savings)</span></h2>
        <span class="text-xl font-bold text-[#1B3A4B]">₱{{ number_format($member->alkansiya_balance, 2) }}</span>
    </div>
    @if ($alkansiyaContribs->isEmpty())
        <p class="text-sm text-gray-400">No Alkansiya contributions recorded.</p>
    @else
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-500 uppercase border-b border-gray-200">
                <tr><th class="py-2 pr-4">Date</th><th class="py-2 pr-4 text-right">Amount</th><th class="py-2 pr-4">Remarks</th></tr>
            </thead>
            <tbody>
                @foreach ($alkansiyaContribs as $c)
                    <tr class="border-b border-gray-100">
                        <td class="py-2 pr-4">{{ $c->contribution_date->format('M d, Y') }}</td>
                        <td class="py-2 pr-4 text-right font-semibold">₱{{ number_format($c->amount, 2) }}</td>
                        <td class="py-2 pr-4 text-gray-500">{{ $c->remarks ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- Contribution breakdown --}}
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Contribution Breakdown <span class="text-xs font-normal text-gray-400">(per-ticket ₱50 split)</span></h2>
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

{{-- Dependents --}}
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ adding: false }">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Dependents</h2>
        <button type="button" @click="adding = !adding" class="text-sm text-blue-600 hover:underline" x-text="adding ? 'Cancel' : '+ Add dependent'"></button>
    </div>

    <form x-show="adding" x-cloak method="POST" action="{{ route('members.dependents.store', $member) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4">
        @csrf
        <input type="text" name="name" placeholder="Full name" required class="rounded border-gray-300 p-2 border text-sm sm:col-span-2">
        <input type="text" name="relationship" placeholder="Relationship" required class="rounded border-gray-300 p-2 border text-sm">
        <input type="date" name="birthdate" class="rounded border-gray-300 p-2 border text-sm">
        <div class="sm:col-span-4 flex justify-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm font-semibold">Save dependent</button>
        </div>
    </form>

    @if($member->dependents->isEmpty())
        <p class="text-sm text-gray-400">No dependents registered.</p>
    @else
        <ul class="text-sm divide-y divide-gray-100">
            @foreach($member->dependents as $dependent)
                <li class="py-2 flex items-center justify-between">
                    <span class="text-gray-700">
                        {{ $dependent->name }}
                        <span class="text-gray-400">· {{ $dependent->relationship }}</span>
                        @unless($dependent->active)<span class="text-xs text-amber-600">(inactive)</span>@endunless
                    </span>
                    <form method="POST" action="{{ route('members.dependents.destroy', [$member, $dependent]) }}" onsubmit="return confirm('Remove this dependent?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline text-xs">Remove</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
</div>

{{-- Benefits --}}
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Benefits</h2>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $benefitSummary['eligible'] ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
            {{ $benefitSummary['eligible'] ? 'Eligible' : 'Not eligible' }} · {{ $benefitSummary['year'] }}
        </span>
    </div>

    <div class="text-sm text-gray-500 mb-4">
        @if($benefitSummary['eligible'])
            {{ $benefitSummary['tickets_ytd'] }} tickets · {{ number_format($benefitSummary['liters_ytd'], 2) }} L diesel this year.
            {{ $benefitSummary['remaining_days'] }} of {{ $benefitSummary['max_days'] }} hospitalization days remaining.
        @else
            {{ $benefitSummary['reason'] }}
        @endif
    </div>

    @if($member->benefits->isEmpty())
        <p class="text-sm text-gray-400">No benefits on record.</p>
    @else
        <ul class="text-sm divide-y divide-gray-100">
            @foreach($member->benefits as $benefit)
                <li class="py-2 flex justify-between">
                    <span class="text-gray-700">
                        {{ ucfirst($benefit->benefit_type) }}
                        <span class="text-gray-400">· {{ $benefit->beneficiary_name }}</span>
                        @if($benefit->days)<span class="text-gray-400">· {{ $benefit->days }} day(s)</span>@endif
                        <span class="text-xs text-gray-400">({{ $benefit->status }})</span>
                    </span>
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

<div class="flex flex-wrap justify-end items-end gap-3 mt-6">
    <form method="GET" action="{{ route('reports.member.statement.pdf', $member) }}" class="flex items-end gap-2">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Statement year</label>
            <select name="year" class="rounded border-gray-300 p-2 border text-sm">
                @for ($y = now()->year; $y >= now()->year - 4; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        </div>
        <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold py-2 px-4 rounded shadow">
            Download Statement (PDF)
        </button>
    </form>
    <a href="{{ route('members.edit', $member) }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 px-4 rounded shadow">Edit Member</a>
</div>
@endsection
