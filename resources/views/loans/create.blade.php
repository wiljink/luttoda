@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('loans.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Loans</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">New Loan Application</h1>
</div>

<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 max-w-xl">
    <form action="{{ route('loans.store') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Member *</label>
            <select name="member_id" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                <option value="">-- Select Member --</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" {{ old('member_id') == $member->id ? 'selected' : '' }}>
                        {{ $member->firstname }} {{ $member->lastname }} ({{ $member->plate_number }})
                        — Savings: ₱{{ number_format($member->savings_balance, 2) }}
                    </option>
                @endforeach
            </select>
            @error('member_id')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Loan Amount (₱) *</label>
                <input type="number" step="0.01" min="1" name="amount" value="{{ old('amount') }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                @error('amount')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Interest Rate (%)</label>
                <input type="number" step="0.01" min="0" name="interest_rate" value="{{ old('interest_rate', 0) }}" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                @error('interest_rate')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Due Date</label>
            <input type="date" name="due_date" value="{{ old('due_date') }}" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            @error('due_date')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Purpose</label>
            <textarea name="purpose" rows="3" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">{{ old('purpose') }}</textarea>
            @error('purpose')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="bg-blue-50 border border-blue-100 rounded p-3 text-sm text-blue-700">
            The loan will be submitted with <strong>Pending</strong> status and requires approval before funds are released.
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <a href="{{ route('loans.index') }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 font-medium">Cancel</a>
            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold shadow-sm transition">
                Submit Application
            </button>
        </div>
    </form>
</div>
@endsection
