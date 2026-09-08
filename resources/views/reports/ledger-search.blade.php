@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Member Savings Ledger — Search</h1>

    <div class="max-w-3xl space-y-6">

        <form method="GET" class="bg-white p-4 shadow-sm rounded-lg flex items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">Search by name or member no.</label>
                <input type="text" name="q" value="{{ $query }}" placeholder="e.g. Grimes or MBR-3382"
                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-md text-sm">Search</button>
        </form>

        @if ($query)
            <div class="bg-white shadow-sm rounded-lg overflow-hidden divide-y divide-gray-100">
                @forelse ($members as $member)
                    <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                        <a href="{{ route('reports.member', $member) }}" class="flex-1">
                            <p class="font-medium text-gray-800">{{ $member->full_name }}</p>
                            <p class="text-sm text-gray-500">{{ $member->member_no }} — {{ $member->route }}</p>
                        </a>
                        <div class="flex items-center gap-4">
                            <span class="text-sm text-gray-600">₱{{ number_format($member->savings_balance, 2) }}</span>
                            <a href="{{ route('reports.member.statement.pdf', ['member' => $member, 'year' => now()->year]) }}"
                               class="text-sm text-blue-600 hover:underline">Statement PDF</a>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-gray-500">No members matched "{{ $query }}".</p>
                @endforelse
            </div>
        @endif
    </div>
@endsection
