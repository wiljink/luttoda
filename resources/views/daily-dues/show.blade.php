@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('daily-dues.index') }}" class="text-sm text-blue-600 hover:underline">← Back to List</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Collection Details</h1>
</div>

<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 max-w-2xl space-y-6">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Member</div>
            <div class="text-gray-800 font-medium">{{ $dailyDue->member->firstname }} {{ $dailyDue->member->lastname }}</div>
            <div class="text-sm text-gray-500">{{ $dailyDue->member->member_no }} · {{ $dailyDue->member->plate_number }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Route</div>
            <div class="text-gray-800 font-medium">{{ $dailyDue->route }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Collection Date</div>
            <div class="text-gray-800 font-medium">{{ \Illuminate\Support\Carbon::parse($dailyDue->collection_date)->format('M d, Y') }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Amount Paid</div>
            <div class="text-green-600 font-bold text-lg">₱{{ number_format($dailyDue->amount_paid, 2) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Ticket Number</div>
            <div class="text-gray-800">{{ $dailyDue->ticket_number ?: '—' }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Collected By</div>
            <div class="text-gray-800">{{ $dailyDue->collectedBy->name ?? '—' }}</div>
        </div>
    </div>

    <div>
        <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Remarks</div>
        <div class="text-gray-800">{{ $dailyDue->remarks ?: '—' }}</div>
    </div>

    <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
        <a href="{{ route('daily-dues.edit', $dailyDue) }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 font-medium">Edit</a>
        <a href="{{ route('daily-dues.index') }}" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold shadow-sm transition">
            Back to List
        </a>
    </div>
</div>
@endsection
