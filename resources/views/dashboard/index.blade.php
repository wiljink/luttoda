@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Dashboard Overview</h1>
    <p class="text-gray-600">Summary of operations for today and this month.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <p class="text-sm font-medium text-gray-500 uppercase">Active Members</p>
        <p class="text-3xl font-bold text-slate-800 mt-2">{{ number_format($stats['total_members']) }}</p>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <p class="text-sm font-medium text-gray-500 uppercase">Today's Collection</p>
        <p class="text-3xl font-bold text-green-600 mt-2">₱{{ number_format($stats['today_collection'], 2) }}</p>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <p class="text-sm font-medium text-gray-500 uppercase">Total Savings</p>
        <p class="text-3xl font-bold text-blue-600 mt-2">₱{{ number_format($stats['total_savings'], 2) }}</p>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <p class="text-sm font-medium text-gray-500 uppercase">Pending Approval (Loans)</p>
        <p class="text-3xl font-bold text-amber-600 mt-2">{{ $stats['pending_loans'] }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Recent Dues Collections -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Last 10 Dues Collections</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase border-b border-gray-200">
                        <th class="p-3">Member</th>
                        <th class="p-3">Route</th>
                        <th class="p-3">Amount</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @forelse($recentDues as $due)
                    <tr>
                        <td class="p-3 font-medium text-gray-800">{{ $due->member->full_name }}</td>
                        <td class="p-3"><span class="px-2 py-1 text-xs rounded bg-slate-100">{{ $due->route }}</span></td>
                        <td class="p-3 font-semibold text-green-600">₱{{ number_format($due->amount_paid, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="p-3 text-center text-gray-400">No records for today.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Loan Applications -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Loan Applications</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase border-b border-gray-200">
                        <th class="p-3">Member</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @forelse($recentLoans as $loan)
                    <tr>
                        <td class="p-3 font-medium text-gray-800">{{ $loan->member->full_name }}</td>
                        <td class="p-3 font-semibold">₱{{ number_format($loan->amount, 2) }}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 text-xs font-bold rounded
                                {{ $loan->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $loan->status === 'approved' || $loan->status === 'active' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $loan->status === 'paid' ? 'bg-blue-100 text-blue-700' : '' }}">
                                {{ strtoupper($loan->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="p-3 text-center text-gray-400">No loan applications recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
