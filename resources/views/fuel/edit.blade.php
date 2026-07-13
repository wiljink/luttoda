@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('fuel.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Fuel Records</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Edit Fuel Record</h1>
</div>

<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 max-w-xl">

    <div class="mb-6 pb-6 border-b border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Member</div>
            <div class="text-gray-800 font-medium">{{ $fuel->member->firstname }} {{ $fuel->member->lastname }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Consumption Date</div>
            <div class="text-gray-800 font-medium">{{ \Illuminate\Support\Carbon::parse($fuel->consumption_date)->format('M d, Y') }}</div>
        </div>
    </div>

    <form action="{{ route('fuel.update', $fuel) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Liters *</label>
                <input type="number" step="0.01" min="0.01" name="liters" value="{{ old('liters', $fuel->liters) }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                @error('liters')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Amount (₱) *</label>
                <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $fuel->amount) }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                @error('amount')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Refill Station</label>
            <input type="text" name="refill_station" value="{{ old('refill_station', $fuel->refill_station) }}" placeholder="e.g. Petron - Carmen" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            @error('refill_station')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <a href="{{ route('fuel.index') }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 font-medium">Cancel</a>
            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold shadow-sm transition">
                Update Record
            </button>
        </div>
    </form>
</div>
@endsection
