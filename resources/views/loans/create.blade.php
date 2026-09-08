@extends('layouts.app')

@section('content')
@php
    $memberMap = $members->mapWithKeys(fn ($m) => [$m->id => [
        'diesel_liters' => (float) $eligibility[$m->id]['diesel_liters'],
        'diesel_amount' => (float) $eligibility[$m->id]['diesel_amount'],
        'cash_ok' => (bool) $eligibility[$m->id]['ok'],
    ]]);
@endphp
<div class="mb-6">
    <a href="{{ route('loans.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Loans</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">New Loan Application</h1>
</div>

<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 max-w-xl"
     x-data="loanForm(@js($memberMap), {{ (float) $dieselFormula['factor'] }}, {{ (float) $dieselFormula['percentage'] }})">
    <form action="{{ route('loans.store') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Loan Type *</label>
            <div class="flex gap-3">
                @foreach (['cash' => 'Cash Loan', 'diesel' => 'Diesel Loan'] as $value => $label)
                    <label class="flex-1 cursor-pointer rounded-lg border border-gray-300 py-2.5 text-center text-sm font-medium text-gray-700 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700">
                        <input type="radio" name="type" value="{{ $value }}" class="sr-only"
                               x-model="type" {{ old('type', 'cash') === $value ? 'checked' : '' }} required>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 mt-1" x-show="type === 'diesel'" x-cloak>
                Amount = un-borrowed diesel litres for {{ $year }} × {{ rtrim(rtrim(number_format($dieselFormula['factor'], 2), '0'), '.') }}
                × {{ rtrim(rtrim(number_format($dieselFormula['percentage'], 2), '0'), '.') }}.
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Member *</label>
            <select name="member_id" required x-model="memberId"
                    class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                <option value="">-- Select Member --</option>
                @foreach ($members as $member)
                    @php($elig = $eligibility[$member->id])
                    <option value="{{ $member->id }}" {{ old('member_id') == $member->id ? 'selected' : '' }}>
                        {{ $member->firstname }} {{ $member->lastname }} ({{ $member->plate_number }})
                        — Savings: ₱{{ number_format($member->savings_balance, 2) }}
                        · {{ $elig['ok'] ? '✓ eligible' : '⚠ not eligible' }} ({{ $elig['tickets'] }} tix / {{ number_format($elig['liters'], 0) }} L)
                        · diesel loan: ₱{{ number_format($elig['diesel_amount'], 2) }} ({{ number_format($elig['diesel_liters'], 0) }} L free)
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">
                Every loan requires at least <strong>{{ $ticketsA }} tickets</strong> or
                <strong>{{ number_format($litersReq, 0) }} L diesel</strong> for {{ $year }}.
                <span x-show="type === 'diesel'" x-cloak>A diesel loan's amount is then un-borrowed litres × the diesel-loan rate.</span>
            </p>
            @error('member_id')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Loan Amount (₱) <span x-show="type === 'cash'">*</span>
                </label>
                <input type="number" step="0.01" min="1" name="amount"
                       x-model="amount" :readonly="type === 'diesel'"
                       :class="type === 'diesel' ? 'bg-gray-100' : ''"
                       class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                <p class="text-xs mt-1" x-show="type === 'diesel'" x-cloak
                   :class="dieselLiters > 0 ? 'text-gray-500' : 'text-red-600'">
                    <span x-show="dieselLiters > 0">
                        <span x-text="fmt(dieselLiters)"></span> L un-borrowed diesel available.
                    </span>
                    <span x-show="dieselLiters <= 0">
                        No un-borrowed diesel litres for this member.
                    </span>
                </p>
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

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Term (months) *</label>
                <input type="number" step="1" min="1" name="term_months" value="{{ old('term_months', 1) }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                <p class="text-xs text-gray-400 mt-1">Amount and interest are split evenly into this many monthly installments.</p>
                @error('term_months')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Penalty Rate (% per month)</label>
                <input type="number" step="0.01" min="0" name="penalty_rate" value="{{ old('penalty_rate', 2.00) }}" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                <p class="text-xs text-gray-400 mt-1">Applied daily (rate ÷ 30 × days overdue) to any unpaid installment.</p>
                @error('penalty_rate')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
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

<script>
    function loanForm(memberMap, dieselFactor, dieselPct) {
        return {
            memberMap,
            dieselFactor,
            dieselPct,
            type: '{{ old('type', 'cash') }}',
            memberId: '{{ old('member_id') }}',
            amount: '{{ old('amount') }}',
            get dieselLiters() {
                return this.memberMap[this.memberId]?.diesel_liters ?? 0;
            },
            get dieselAmount() {
                return this.memberMap[this.memberId]?.diesel_amount ?? 0;
            },
            fmt(n) {
                return Number(n).toLocaleString(undefined, { maximumFractionDigits: 2 });
            },
            sync() {
                if (this.type === 'diesel') {
                    this.amount = this.dieselAmount ? this.dieselAmount.toFixed(2) : '';
                }
            },
            init() {
                this.$watch('type', () => this.sync());
                this.$watch('memberId', () => this.sync());
                this.sync();
            },
        };
    }
</script>
@endsection
