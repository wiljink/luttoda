@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('tickets.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Tickets</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Add Ticket Booklet</h1>
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

    <form action="{{ route('tickets.store') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Route (optional)</label>
            <select name="route" class="w-full rounded border-gray-300 p-2 border">
                <option value="">-- Not route-specific --</option>
                <option value="Carmen" {{ old('route') === 'Carmen' ? 'selected' : '' }}>Carmen</option>
                <option value="Cogon" {{ old('route') === 'Cogon' ? 'selected' : '' }}>Cogon</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Prefix (optional)</label>
            <input type="text" name="prefix" value="{{ old('prefix') }}" placeholder="e.g. CAR-" class="w-full rounded border-gray-300 p-2 border">
            <p class="text-xs text-gray-400 mt-1">Leave blank if tickets are plain numbers only.</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">From Ticket # *</label>
                <input type="number" name="from_number" value="{{ old('from_number') }}" required min="1" class="w-full rounded border-gray-300 p-2 border">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">To Ticket # *</label>
                <input type="number" name="to_number" value="{{ old('to_number') }}" required min="1" class="w-full rounded border-gray-300 p-2 border">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Digit Padding *</label>
            <select name="padding" required class="w-full rounded border-gray-300 p-2 border">
                <option value="4" {{ old('padding', 4) == 4 ? 'selected' : '' }}>4 digits (e.g. 0001)</option>
                <option value="5" {{ old('padding') == 5 ? 'selected' : '' }}>5 digits (e.g. 00001)</option>
                <option value="3" {{ old('padding') == 3 ? 'selected' : '' }}>3 digits (e.g. 001)</option>
                <option value="6" {{ old('padding') == 6 ? 'selected' : '' }}>6 digits (e.g. 000001)</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">Match this to how the numbers are printed on the physical tickets.</p>
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded shadow transition">
                Add Ticket Range
            </button>
        </div>
    </form>
</div>
@endsection
