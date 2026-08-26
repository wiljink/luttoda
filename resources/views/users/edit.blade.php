@extends('layouts.app')

@section('content')
<h1 class="text-3xl font-bold text-gray-800 mb-6">Edit User</h1>

<div class="bg-white rounded-lg shadow-sm p-6 max-w-lg">
    <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">New Password <span class="text-gray-400 text-xs">(leave blank to keep current)</span></label>
            <input type="password" name="password" class="w-full border-gray-300 rounded-lg shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
            <input type="password" name="password_confirmation" class="w-full border-gray-300 rounded-lg shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
            <select name="role" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                @foreach($roles as $role)
                    <option value="{{ $role }}" {{ old('role', $currentRole) === $role ? 'selected' : '' }} class="capitalize">
                        {{ ucfirst($role) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-end gap-2 pt-4">
            <a href="{{ route('users.index') }}" class="px-4 py-2 rounded-lg text-sm border border-gray-300 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm bg-slate-800 text-white hover:bg-slate-700">Update User</button>
        </div>
    </form>
</div>
@endsection
