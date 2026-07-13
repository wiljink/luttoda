@extends('layouts.app')

@section('title', 'Income & Expenses')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
        <div>
            <p class="text-xs font-semibold tracking-widest text-amber-600 uppercase mb-1">Financial Records</p>
            <h1 class="text-2xl font-bold text-slate-900">Income &amp; Expenses</h1>
        </div>
        <a href="{{ route('income-expenses.create') }}"
           class="inline-flex items-center gap-2 bg-[#1B3A4B] hover:bg-[#15303e] text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Record transaction
        </a>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1.5">Total income</p>
            <p class="text-2xl font-bold text-emerald-600 font-mono">₱{{ number_format($totalIncome, 2) }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1.5">Total expense</p>
            <p class="text-2xl font-bold text-rose-600 font-mono">₱{{ number_format($totalExpense, 2) }}</p>
        </div>
        <div class="bg-[#1B3A4B] rounded-xl p-5">
            <p class="text-xs font-medium text-white/60 uppercase tracking-wide mb-1.5">Net balance</p>
            <p class="text-2xl font-bold text-white font-mono">₱{{ number_format($totalIncome - $totalExpense, 2) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('income-expenses.index') }}"
          class="flex flex-wrap items-end gap-3 mb-5 bg-white border border-slate-200 rounded-xl p-4">
        <div class="flex gap-2">
            @php $types = ['' => 'All', 'income' => 'Income', 'expense' => 'Expense']; @endphp
            @foreach ($types as $value => $label)
                <a href="{{ route('income-expenses.index', array_filter(['type' => $value ?: null, 'from' => request('from'), 'to' => request('to')])) }}"
                   class="px-3.5 py-1.5 rounded-full text-xs font-semibold border transition-colors
                          {{ request('type', '') === $value
                                ? 'bg-[#1B3A4B] text-white border-[#1B3A4B]'
                                : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="flex items-end gap-2 ml-auto">
            <input type="hidden" name="type" value="{{ request('type') }}">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="rounded-lg border-slate-300 text-sm focus:border-[#1B3A4B] focus:ring-[#1B3A4B]">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="rounded-lg border-slate-300 text-sm focus:border-[#1B3A4B] focus:ring-[#1B3A4B]">
            </div>
            <button type="submit"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                Apply
            </button>
            @if (request('from') || request('to'))
                <a href="{{ route('income-expenses.index', ['type' => request('type')]) }}"
                   class="text-sm font-medium text-slate-400 hover:text-slate-600 px-2 py-2">Clear</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Description</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3 text-right">Amount</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-5 py-3.5 text-slate-600">{{ $record->transaction_date->format('M d, Y') }}</td>
                        <td class="px-5 py-3.5 font-medium text-slate-800">{{ $record->category }}</td>
                        <td class="px-5 py-3.5 text-slate-600 max-w-xs truncate">{{ $record->description }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1
                                {{ $record->type === 'income' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">
                                {{ ucfirst($record->type) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono {{ $record->type === 'income' ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $record->type === 'income' ? '+' : '-' }}₱{{ number_format($record->amount, 2) }}
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('income-expenses.show', $record) }}" class="text-sm font-medium text-[#1B3A4B] hover:underline">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                            No transactions recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $records->appends(request()->query())->links() }}
    </div>
</div>
@endsection
