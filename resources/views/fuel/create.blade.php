@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('fuel.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Fuel Records</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Add Fuel Consumption Record</h1>
</div>

<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 max-w-xl">
    <form action="{{ route('fuel.store') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Member *</label>
            <select name="member_id" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                <option value="">-- Select Member --</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" {{ old('member_id') == $member->id ? 'selected' : '' }}>
                        {{ $member->firstname }} {{ $member->lastname }} ({{ $member->plate_number }})
                    </option>
                @endforeach
            </select>
            @error('member_id')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Consumption Date *</label>
            <input type="date" name="consumption_date" value="{{ old('consumption_date', today()->toDateString()) }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            @error('consumption_date')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-4"
             x-data="fuelAmount({{ (float) $dieselPrice }}, '{{ old('liters') }}', '{{ old('amount') }}')">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Liters *</label>
                <input type="number" step="0.01" min="0.01" name="liters" required
                       x-model="liters" @input="recompute()"
                       class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                @error('liters')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Amount (₱)</label>
                <input type="number" step="0.01" min="0" name="amount" x-model="amount"
                       class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                <p class="text-xs text-gray-400 mt-1">
                    Auto-fills as liters &times; <span class="font-medium">₱{{ number_format($dieselPrice, 2) }}</span>/L
                    when you enter liters (<a href="{{ route('settings.index') }}" class="text-blue-600 hover:underline">change price in Settings</a>).
                    Editable if the pump total differs.
                </p>
                @error('amount')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Refill Station</label>
            <input type="text" name="refill_station" value="{{ old('refill_station') }}" placeholder="e.g. Petron - Carmen" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            @error('refill_station')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <a href="{{ route('fuel.index') }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 font-medium">Cancel</a>
            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold shadow-sm transition">
                Save Record
            </button>
        </div>
    </form>
</div>

<script>
    function fuelAmount(price, oldLiters, oldAmount) {
        return {
            price: price,
            liters: oldLiters,
            amount: oldAmount,
            recompute() {
                const l = parseFloat(this.liters);
                this.amount = isNaN(l) ? '' : (l * this.price).toFixed(2);
            },
        };
    }
</script>
@endsection
