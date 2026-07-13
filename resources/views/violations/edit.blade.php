@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('members.violations.index', $member) }}" class="text-sm text-blue-600 hover:underline">← Back to Violations</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Edit Violation — {{ $member->firstname }} {{ $member->lastname }}</h1>
</div>

<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 max-w-xl">
    @if ($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('members.violations.update', [$member, $violation]) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Violation Date *</label>
            <input type="date" name="violation_date" value="{{ old('violation_date', $violation->violation_date->format('Y-m-d')) }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Type / Reason *</label>
            <input type="text" name="type" value="{{ old('type', $violation->type) }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="4" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">{{ old('notes', $violation->notes) }}</textarea>
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <a href="{{ route('members.violations.index', $member) }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 font-medium">Cancel</a>
            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold shadow-sm transition">
                Update Violation
            </button>
        </div>
    </form>
</div>
@endsection
