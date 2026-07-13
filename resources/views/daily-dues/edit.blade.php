@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('daily-dues.index') }}" class="text-sm text-blue-600 hover:underline">← Back to List</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Edit Collection</h1>
</div>

<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 max-w-2xl">

    <div class="mb-6 pb-6 border-b border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Member</div>
            <div class="text-gray-800 font-medium">{{ $dailyDue->member->firstname }} {{ $dailyDue->member->lastname }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Collection Date</div>
            <div class="text-gray-800 font-medium">{{ \Illuminate\Support\Carbon::parse($dailyDue->collection_date)->format('M d, Y') }}</div>
        </div>
    </div>

    <form action="{{ route('daily-dues.update', $dailyDue) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Ticket Number</label>
            <input type="text" name="ticket_number" value="{{ old('ticket_number', $dailyDue->ticket_number) }}" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            @error('ticket_number')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Remarks</label>
            <textarea name="remarks" rows="3" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">{{ old('remarks', $dailyDue->remarks) }}</textarea>
            @error('remarks')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <a href="{{ route('daily-dues.index') }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 font-medium">Cancel</a>
            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold shadow-sm transition">
                Update
            </button>
        </div>
    </form>
</div>
@endsection
