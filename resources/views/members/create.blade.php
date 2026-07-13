@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('members.index') }}" class="text-sm text-blue-600 hover:underline">← Back to List</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Add New Member</h1>
</div>

<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 max-w-3xl">
    <form action="{{ route('members.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Member Number (MBR-XXXX) *</label>
                <input type="text" name="member_no" value="{{ old('member_no') }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Plate Number *</label>
                <input type="text" name="plate_number" value="{{ old('plate_number') }}" required placeholder="e.g. ABC-1234" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">First Name *</label>
                <input type="text" name="firstname" value="{{ old('firstname') }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Middle Name</label>
                <input type="text" name="middlename" value="{{ old('middlename') }}" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Last Name *</label>
                <input type="text" name="lastname" value="{{ old('lastname') }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Operator Name *</label>
                <input type="text" name="operator_name" value="{{ old('operator_name') }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Operating Route *</label>
                <select name="route" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                    <option value="">-- Select Route --</option>
                    <option value="Carmen" {{ old('route') === 'Carmen' ? 'selected' : '' }}>Carmen</option>
                    <option value="Cogon" {{ old('route') === 'Cogon' ? 'selected' : '' }}>Cogon</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Contact Number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number') }}" placeholder="e.g. 09123456789" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Date Joined *</label>
                <input type="date" name="date_joined" value="{{ old('date_joined', today()->toDateString()) }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Address</label>
                <input type="text" name="address" value="{{ old('address') }}" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Status *</label>
                <select name="status" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                    <option value="active" {{ old('status', 'inactive') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', 'inactive') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <a href="{{ route('members.index') }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 font-medium">Cancel</a>
            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold shadow-sm transition">
                Save Member
            </button>
        </div>
    </form>
</div>
@endsection
