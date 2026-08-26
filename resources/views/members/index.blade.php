@extends('layouts.app')

@section('content')
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Members List</h1>
        <p class="text-sm text-gray-600">Manage member information, plate numbers, and savings balances.</p>
    </div>
    <a href="{{ route('members.create') }}" class="mt-4 md:mt-0 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow transition duration-150">
        + Add Member
    </a>
</div>

<!-- Filters / Search Box -->
<form method="GET" action="{{ route('members.index') }}" class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
    <div>
        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Search</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, plate number, or member no..." class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 p-2 border">
    </div>
    <div>
        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Route</label>
        <select name="route" class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 p-2 border">
            <option value="">All routes</option>
            <option value="Carmen" {{ request('route') === 'Carmen' ? 'selected' : '' }}>Carmen</option>
            <option value="Cogon" {{ request('route') === 'Cogon' ? 'selected' : '' }}>Cogon</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Status</label>
        <select name="status" class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 p-2 border">
            <option value="">All</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
    <div class="flex items-end">
        <button type="submit" class="w-full bg-slate-700 hover:bg-slate-800 text-white font-semibold p-2 rounded shadow transition duration-150">
            Filter / Search
        </button>
    </div>
</form>

<!-- Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase border-b border-gray-200">
                    <th class="p-3">ID / Member No</th>
                    <th class="p-3">Full Name</th>
                    <th class="p-3">Plate Number</th>
                    <th class="p-3">Route</th>
                    <th class="p-3 text-right">Savings Balance</th>
                    <th class="p-3 text-center">Status</th>
                    <th class="p-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100">
                @forelse($members as $member)
                <tr>
                    <td class="p-3 font-mono font-bold text-gray-700">{{ $member->member_no }}</td>
                    <td class="p-3">
                        <div class="flex items-center space-x-3">
                            @if ($member->photo_url)
                                <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0">
                            @else
                                <div class="w-8 h-8 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400 flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                </div>
                            @endif
                            <div>
                                <a href="{{ route('members.show', $member->id) }}" class="text-blue-600 font-semibold hover:underline">
                                    {{ $member->full_name }}
                                </a>
                                <span class="block text-xs text-gray-400">Operator: {{ $member->operator_name }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="p-3 font-semibold">{{ $member->plate_number }}</td>
                    <td class="p-3"><span class="px-2 py-0.5 text-xs rounded bg-slate-100">{{ $member->route }}</span></td>
                    <td class="p-3 text-right font-semibold text-blue-600">₱{{ number_format($member->savings_balance, 2) }}</td>
                    <td class="p-3 text-center">
                        <span class="px-2 py-1 text-xs font-bold rounded-full {{ $member->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ strtoupper($member->status) }}
                        </span>
                    </td>
                    <td class="p-3 text-center space-x-2">
                        <a href="{{ route('reports.member', $member->id) }}" class="text-slate-700 hover:text-slate-900 font-medium">Ledger</a>
                        <a href="{{ route('members.edit', $member->id) }}" class="text-amber-600 hover:text-amber-800 font-medium">Edit</a>
                        <form action="{{ route('members.destroy', $member->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to soft-delete this member?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-4 text-center text-gray-400">No members found in the system.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">
        {{ $members->links() }}
    </div>
</div>
@endsection
