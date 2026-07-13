@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('tickets.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Tickets</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Edit Range {{ $from }} – {{ $to }}</h1>
    <p class="text-sm text-gray-500 mt-1">{{ $count }} ticket(s) in this range.</p>
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

    <form action="{{ route('tickets.bulkUpdateRoute') }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="ticket_ids" value="{{ $ticket_ids }}">

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Route</label>
            <select name="route" class="w-full rounded border-gray-300 p-2 border">
                <option value="">-- Not route-specific --</option>
                <option value="Carmen" {{ old('route', $route ?? null) === 'Carmen' ? 'selected' : '' }}>Carmen</option>
                <option value="Cogon" {{ old('route', $route ?? null) === 'Cogon' ? 'selected' : '' }}>Cogon</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">
                This will apply to all {{ $count }} ticket(s) in the range {{ $from }} – {{ $to }}.
            </p>
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded shadow transition">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
