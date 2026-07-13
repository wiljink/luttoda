@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('fuel.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Fuel Records</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Fuel Record Details</h1>
</div>

<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 max-w-2xl space-y-6">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Member</div>
            <div class="text-gray-800 font-medium">{{ $fuel->member->firstname }} {{ $fuel->member->lastname }}</div>
            <div class="text-sm text-gray-500">{{ $fuel->member->member_no }} · {{ $fuel->member->plate_number }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Consumption Date</div>
            <div class="text-gray-800 font-medium">{{ \Illuminate\Support\Carbon::parse($fuel->consumption_date)->format('M d, Y') }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Liters</div>
            <div class="text-gray-800 font-medium">{{ number_format($fuel->liters, 2) }} L</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Amount</div>
            <div class="text-green-600 font-bold text-lg">₱{{ number_format($fuel->amount, 2) }}</div>
        </div>
    </div>

    <div>
        <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Refill Station</div>
        <div class="text-gray-800">{{ $fuel->refill_station ?: '—' }}</div>
    </div>

    <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
        <a href="{{ route('fuel.edit', $fuel) }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 font-medium">Edit</a>
        <a href="{{ route('fuel.index') }}" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold shadow-sm transition">
            Back to List
        </a>
    </div>
</div>
@endsection
