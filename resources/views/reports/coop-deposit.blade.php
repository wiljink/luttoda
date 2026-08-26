@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Coop Deposit Report</h1>

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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Total Coop Deposit</p>
                <p class="text-xl font-semibold text-gray-800">₱{{ number_format($total_coop_deposit, 2) }}</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Total Liters</p>
                <p class="text-xl font-semibold text-gray-800">{{ number_format($total_liters, 2) }} L</p>
            </div>
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <p class="text-sm text-gray-500">Transactions</p>
                <p class="text-xl font-semibold text-gray-800">{{ $transaction_count }}</p>
            </div>
        </div>
    </div>
@endsection
