@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="{{ route('members.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Members</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">
            Violations — {{ $member->firstname }} {{ $member->lastname }}
        </h1>
    </div>
    <a href="{{ route('members.violations.create', $member) }}"
       class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold shadow-sm transition">
        + Add Violation
    </a>
</div>

@if (session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm p-3 rounded">
        {{ session('success') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Sanction</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Notes</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($violations as $violation)
                <tr>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $violation->violation_date->format('M d, Y') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $violation->type }}</td>
                    <td class="px-4 py-3 text-sm">
                        @if ($violation->sanction)
                            <span @class([
                                'inline-block text-xs font-semibold px-2 py-0.5 rounded-full',
                                'bg-orange-100 text-orange-700' => $violation->sanction === 'suspension',
                                'bg-red-100 text-red-700' => in_array($violation->sanction, ['termination', 'dismembership'], true),
                            ])>
                                {{ ucfirst($violation->sanction) }}@if ($violation->sanction === 'suspension' && $violation->sanction_until) · until {{ $violation->sanction_until->format('M d, Y') }}@endif
                            </span>
                            @if ($violation->sanction_lifted_at)
                                <span class="block text-xs text-gray-400 mt-0.5">lifted {{ $violation->sanction_lifted_at->format('M d, Y') }}</span>
                            @endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">{{ $violation->notes ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-right space-x-3">
                        <a href="{{ route('members.violations.edit', [$member, $violation]) }}"
                           class="text-blue-600 hover:underline">Edit</a>
                        <form action="{{ route('members.violations.destroy', [$member, $violation]) }}"
                              method="POST" class="inline"
                              onsubmit="return confirm('Delete this violation?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                        No violations recorded yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $violations->links() }}
</div>
@endsection
