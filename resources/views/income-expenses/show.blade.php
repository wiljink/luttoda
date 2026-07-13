@extends('layouts.app')

@section('title', 'Transaction Details')

@section('content')
<div class="max-w-xl mx-auto px-4 py-8">

    <a href="{{ route('income-expenses.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-700 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to records
    </a>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-dashed border-slate-200 flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold tracking-widest text-amber-600 uppercase mb-1">{{ $incomeExpense->category }}</p>
                <h1 class="text-xl font-bold text-slate-900">{{ $incomeExpense->description }}</h1>
            </div>
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1
                {{ $incomeExpense->type === 'income' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">
                {{ ucfirst($incomeExpense->type) }}
            </span>
        </div>

        <div class="px-6 py-5 grid grid-cols-2 gap-y-4 text-sm">
            <div>
                <p class="text-slate-400 text-xs mb-0.5">Transaction date</p>
                <p class="font-medium text-slate-800">{{ $incomeExpense->transaction_date->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-slate-400 text-xs mb-0.5">Amount</p>
                <p class="font-mono font-semibold {{ $incomeExpense->type === 'income' ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ $incomeExpense->type === 'income' ? '+' : '-' }}₱{{ number_format($incomeExpense->amount, 2) }}
                </p>
            </div>
            @if ($incomeExpense->recorded_by)
                <div class="col-span-2">
                    <p class="text-slate-400 text-xs mb-0.5">Recorded by</p>
                    <p class="font-medium text-slate-800">{{ $incomeExpense->recordedBy->name ?? 'User #' . $incomeExpense->recorded_by }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('income-expenses.edit', $incomeExpense) }}"
           class="bg-[#1B3A4B] hover:bg-[#15303e] text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
            Edit transaction
        </a>

        <form method="POST" action="{{ route('income-expenses.destroy', $incomeExpense) }}"
              onsubmit="return confirm('Delete this transaction? This cannot be undone.');" class="ml-auto">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="text-sm font-medium text-slate-400 hover:text-rose-600 px-4 py-2.5 transition-colors">
                Delete transaction
            </button>
        </form>
    </div>
</div>
@endsection
