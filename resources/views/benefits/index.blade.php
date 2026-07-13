@extends('layouts.app')

@section('title', 'Benefit Claims')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
        <div>
            <p class="text-xs font-semibold tracking-widest text-amber-600 uppercase mb-1">Member Benefits</p>
            <h1 class="text-2xl font-bold text-slate-900">Claims Ledger</h1>
        </div>
        <a href="{{ route('benefits.create') }}"
           class="inline-flex items-center gap-2 bg-[#1B3A4B] hover:bg-[#15303e] text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            File a claim
        </a>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Status filter --}}
    <div class="flex flex-wrap gap-2 mb-5">
        @php
            $statuses = ['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'released' => 'Released'];
        @endphp
        @foreach ($statuses as $value => $label)
            <a href="{{ route('benefits.index', $value ? ['status' => $value] : []) }}"
               class="px-3.5 py-1.5 rounded-full text-xs font-semibold border transition-colors
                      {{ request('status', '') === $value
                            ? 'bg-[#1B3A4B] text-white border-[#1B3A4B]'
                            : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Table --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-5 py-3">Member</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3">Claim date</th>
                    <th class="px-5 py-3 text-right">Amount</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($benefits as $benefit)
                    @php
                        $typeStyles = [
                            'hospitalization' => ['label' => 'HOSP', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200'],
                            'burial'          => ['label' => 'BUR',  'class' => 'bg-slate-100 text-slate-700 ring-slate-300'],
                            'sss'             => ['label' => 'SSS',  'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
                        ];
                        $type = $typeStyles[$benefit->benefit_type] ?? ['label' => strtoupper($benefit->benefit_type), 'class' => 'bg-slate-100 text-slate-700 ring-slate-300'];

                        $statusStyles = [
                            'pending'  => 'bg-amber-50 text-amber-700 ring-amber-200',
                            'approved' => 'bg-sky-50 text-sky-700 ring-sky-200',
                            'rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
                            'released' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-5 py-3.5">
                            <a href="{{ route('benefits.show', $benefit) }}" class="font-medium text-slate-800 hover:text-[#1B3A4B]">
                                {{ $benefit->member->firstname }} {{ $benefit->member->lastname }}
                            </a>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center justify-center w-14 rounded-md px-1.5 py-1 text-[11px] font-bold tracking-wide ring-1 {{ $type['class'] }}">
                                {{ $type['label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-slate-600">{{ $benefit->claim_date->format('M d, Y') }}</td>
                        <td class="px-5 py-3.5 text-right font-mono text-slate-800">₱{{ number_format($benefit->amount, 2) }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusStyles[$benefit->status] ?? 'bg-slate-100 text-slate-700 ring-slate-300' }}">
                                {{ ucfirst($benefit->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('benefits.show', $benefit) }}" class="text-sm font-medium text-[#1B3A4B] hover:underline">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                            No claims match this filter yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $benefits->links() }}
    </div>
</div>
@endsection
