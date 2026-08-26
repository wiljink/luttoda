@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Dashboard</h1>

    <div class="space-y-6">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Active Members</p>
                <p class="text-2xl font-semibold text-gray-800">{{ $stats['total_members'] }}</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Today's Collection</p>
                <p class="text-2xl font-semibold text-green-700">₱{{ number_format($stats['today_collection'], 2) }}</p>
                <p class="text-xs text-gray-400">{{ $stats['today_collectors'] }} collector(s)</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Total Member Savings</p>
                <p class="text-2xl font-semibold text-gray-800">₱{{ number_format($stats['total_savings'], 2) }}</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Pending Loans</p>
                <p class="text-2xl font-semibold text-yellow-700">{{ $stats['pending_loans'] }}</p>
                <p class="text-xs text-gray-400">₱{{ number_format($stats['active_loans_balance'], 2) }} active balance</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Pending Benefits</p>
                <p class="text-2xl font-semibold text-yellow-700">{{ $stats['pending_benefits'] }}</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">This Month's Income</p>
                <p class="text-2xl font-semibold text-green-700">₱{{ number_format($stats['month_income'], 2) }}</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">This Month's Expenses</p>
                <p class="text-2xl font-semibold text-red-700">₱{{ number_format($stats['month_expense'], 2) }}</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Fuel Consumed (month)</p>
                <p class="text-2xl font-semibold text-gray-800">{{ number_format($stats['month_fuel_liters'], 2) }} L</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <h3 class="px-4 py-3 font-medium bg-gray-50 border-b">Recent Daily Dues</h3>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recentDues as $due)
                            <tr>
                                <td class="px-4 py-2">{{ $due->member->full_name ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $due->route }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $due->collection_date->format('M d') }}</td>
                                <td class="px-4 py-2 text-right">₱{{ number_format($due->amount_paid, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-center text-gray-500" colspan="4">No dues recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <h3 class="px-4 py-3 font-medium bg-gray-50 border-b">Recent Loans</h3>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recentLoans as $loan)
                            <tr>
                                <td class="px-4 py-2">{{ $loan->member->full_name ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ ucfirst($loan->status) }}</td>
                                <td class="px-4 py-2 text-right">₱{{ number_format($loan->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-center text-gray-500" colspan="3">No loans recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
