@extends('layouts.app')

@section('title', 'Claim Details')

@section('content')
@php
    $statusStyles = [
        'pending'  => 'bg-amber-50 text-amber-700 ring-amber-200',
        'approved' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'released' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    ];
    $typeLabels = [
        'hospitalization' => 'Hospitalization',
        'burial' => 'Burial',
        'sss' => 'SSS',
    ];
@endphp

<div class="max-w-2xl mx-auto px-4 py-8">

    <a href="{{ route('benefits.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-700 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to ledger
    </a>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Claim card, styled like a passbook entry --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-dashed border-slate-200 flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold tracking-widest text-amber-600 uppercase mb-1">
                    {{ $typeLabels[$benefit->benefit_type] ?? ucfirst($benefit->benefit_type) }} Claim
                </p>
                <h1 class="text-xl font-bold text-slate-900">
                    {{ $benefit->member->firstname }} {{ $benefit->member->lastname }}
                </h1>
            </div>
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusStyles[$benefit->status] ?? 'bg-slate-100 text-slate-700 ring-slate-300' }}">
                {{ ucfirst($benefit->status) }}
            </span>
        </div>

        <div class="px-6 py-5 grid grid-cols-2 gap-y-4 text-sm">
            <div>
                <p class="text-slate-400 text-xs mb-0.5">Claim date</p>
                <p class="font-medium text-slate-800">{{ $benefit->claim_date->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-slate-400 text-xs mb-0.5">Amount</p>
                <p class="font-mono font-semibold text-slate-800">₱{{ number_format($benefit->amount, 2) }}</p>
            </div>
            @if ($benefit->benefit_type === 'hospitalization')
                <div>
                    <p class="text-slate-400 text-xs mb-0.5">Days confined</p>
                    <p class="font-medium text-slate-800">{{ $benefit->days ?? 1 }}</p>
                </div>
            @endif
            @if ($benefit->processed_date)
                <div>
                    <p class="text-slate-400 text-xs mb-0.5">Processed</p>
                    <p class="font-medium text-slate-800">{{ $benefit->processed_date->format('M d, Y') }}</p>
                </div>
            @endif
            @if ($benefit->remarks)
                <div class="col-span-2">
                    <p class="text-slate-400 text-xs mb-0.5">Remarks</p>
                    <p class="text-slate-700">{{ $benefit->remarks }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3">

        @if ($benefit->status === 'pending')
            <form method="POST" action="{{ route('benefits.approve', $benefit) }}">
                @csrf
                <input type="hidden" name="status" value="approved">
                <button type="submit"
                        class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
                    Approve claim
                </button>
            </form>
            <form method="POST" action="{{ route('benefits.approve', $benefit) }}">
                @csrf
                <input type="hidden" name="status" value="rejected">
                <button type="submit"
                        class="bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
                    Reject claim
                </button>
            </form>
        @endif

        @if ($benefit->status === 'approved')
            <form method="POST" action="{{ route('benefits.release', $benefit) }}">
                @csrf
                <button type="submit"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
                    Release benefit
                </button>
            </form>
        @endif

        @if (in_array($benefit->status, ['pending', 'rejected']))
            <form method="POST" action="{{ route('benefits.destroy', $benefit) }}"
                  onsubmit="return confirm('Delete this claim? This cannot be undone.');" class="ml-auto">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="text-sm font-medium text-slate-400 hover:text-rose-600 px-4 py-2.5 transition-colors">
                    Delete claim
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
