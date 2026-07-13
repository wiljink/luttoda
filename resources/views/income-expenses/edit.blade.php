@extends('layouts.app')

@section('title', 'Edit Transaction')

@section('content')
<div class="max-w-xl mx-auto px-4 py-8">

    <p class="text-xs font-semibold tracking-widest text-amber-600 uppercase mb-1">Financial Records</p>
    <h1 class="text-2xl font-bold text-slate-900 mb-6">Edit Transaction</h1>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('income-expenses.update', $incomeExpense) }}" class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Type</label>
            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1
                {{ $incomeExpense->type === 'income' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">
                {{ ucfirst($incomeExpense->type) }}
            </span>
            <p class="mt-1 text-xs text-slate-400">Type can't be changed once recorded. Delete and re-record if this was set wrong.</p>
        </div>

        <div>
            <label for="transaction_date" class="block text-sm font-medium text-slate-700 mb-1.5">Transaction date</label>
            <input type="date" id="transaction_date" name="transaction_date"
                   value="{{ old('transaction_date', $incomeExpense->transaction_date->format('Y-m-d')) }}" required
                   class="w-full rounded-lg border-slate-300 text-sm focus:border-[#1B3A4B] focus:ring-[#1B3A4B]">
        </div>

        <div>
            <label for="category" class="block text-sm font-medium text-slate-700 mb-1.5">Category</label>
            <input type="text" id="category" name="category" value="{{ old('category', $incomeExpense->category) }}" required
                   class="w-full rounded-lg border-slate-300 text-sm focus:border-[#1B3A4B] focus:ring-[#1B3A4B]">
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
            <input type="text" id="description" name="description" value="{{ old('description', $incomeExpense->description) }}" required maxlength="255"
                   class="w-full rounded-lg border-slate-300 text-sm focus:border-[#1B3A4B] focus:ring-[#1B3A4B]">
        </div>

        <div>
            <label for="amount" class="block text-sm font-medium text-slate-700 mb-1.5">Amount</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">₱</span>
                <input type="number" id="amount" name="amount" value="{{ old('amount', $incomeExpense->amount) }}" required min="0.01" step="0.01"
                       class="w-full rounded-lg border-slate-300 text-sm pl-7 focus:border-[#1B3A4B] focus:ring-[#1B3A4B]">
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('income-expenses.show', $incomeExpense) }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</a>
            <button type="submit"
                    class="bg-[#1B3A4B] hover:bg-[#15303e] text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors">
                Save changes
            </button>
        </div>
    </form>
</div>
@endsection
