@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('members.index') }}" class="text-sm text-blue-600 hover:underline">← Back to List</a>
    <div class="flex items-center justify-between mt-2">
        <h1 class="text-2xl font-bold text-gray-800">Edit Member</h1>
        <a href="{{ route('members.violations.index', $member) }}" class="bg-red-50 hover:bg-red-100 text-red-700 text-sm font-semibold py-2 px-4 rounded border border-red-200">
            ⚠ Manage Violations
        </a>
    </div>
</div>

<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 max-w-3xl">
    <form action="{{ route('members.update', $member) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Photo</label>
            <div class="flex items-center space-x-4">
                @if ($member->photo_url)
                    <img id="photo-preview" src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="w-20 h-20 rounded-full object-cover border border-gray-200">
                    <div id="photo-preview-empty" class="w-20 h-20 rounded-full bg-gray-100 border border-gray-200 hidden items-center justify-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    </div>
                @else
                    <div id="photo-preview-empty" class="w-20 h-20 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    </div>
                    <img id="photo-preview" src="" alt="Photo preview" class="w-20 h-20 rounded-full object-cover border border-gray-200 hidden">
                @endif
                <div>
                    <input type="file" name="photo" accept="image/*" onchange="
                        const f = this.files[0];
                        const img = document.getElementById('photo-preview');
                        const empty = document.getElementById('photo-preview-empty');
                        if (f) { img.src = URL.createObjectURL(f); img.classList.remove('hidden'); empty.classList.add('hidden'); document.getElementById('remove_photo').checked = false; }
                    " class="text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded file:border-0 file:bg-blue-50 file:text-blue-700 file:font-semibold hover:file:bg-blue-100">
                    @if ($member->photo_url)
                        <label class="flex items-center mt-2 text-xs text-gray-500">
                            <input type="checkbox" id="remove_photo" name="remove_photo" value="1" class="mr-1.5 rounded border-gray-300" onchange="
                                const img = document.getElementById('photo-preview');
                                const empty = document.getElementById('photo-preview-empty');
                                if (this.checked) { img.classList.add('hidden'); empty.classList.remove('hidden'); }
                                else { img.classList.remove('hidden'); empty.classList.add('hidden'); }
                            ">
                            Remove current photo
                        </label>
                    @endif
                </div>
            </div>
            @error('photo')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            <p class="text-xs text-gray-400 mt-1">JPG or PNG, up to 2MB.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Member Number (MBR-XXXX) *</label>
                <input type="text" name="member_no" value="{{ old('member_no', $member->member_no) }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Plate Number *</label>
                <input type="text" name="plate_number" value="{{ old('plate_number', $member->plate_number) }}" required placeholder="e.g. ABC-1234" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">First Name *</label>
                <input type="text" name="firstname" value="{{ old('firstname', $member->firstname) }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Middle Name</label>
                <input type="text" name="middlename" value="{{ old('middlename', $member->middlename) }}" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Last Name *</label>
                <input type="text" name="lastname" value="{{ old('lastname', $member->lastname) }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Operator Name *</label>
                <input type="text" name="operator_name" value="{{ old('operator_name', $member->operator_name) }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Operating Route *</label>
                <select name="route" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                    <option value="">-- Select Route --</option>
                    <option value="Carmen" {{ old('route', $member->route) === 'Carmen' ? 'selected' : '' }}>Carmen</option>
                    <option value="Cogon" {{ old('route', $member->route) === 'Cogon' ? 'selected' : '' }}>Cogon</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Contact Number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number', $member->contact_number) }}" placeholder="e.g. 09123456789" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Date Joined *</label>
                <input type="date" name="date_joined" value="{{ old('date_joined', optional($member->date_joined)->format('Y-m-d')) }}" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Address</label>
                <input type="text" name="address" value="{{ old('address', $member->address) }}" class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Status *</label>
                <select name="status" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                    <option value="active" {{ old('status', $member->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $member->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <a href="{{ route('members.index') }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 font-medium">Cancel</a>
            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold shadow-sm transition">
                Update Member
            </button>
        </div>
    </form>
</div>
@endsection
