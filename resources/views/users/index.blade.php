@extends('layouts.app')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
        <p class="text-gray-500">Manage system users and their roles.</p>
    </div>
    <a href="{{ route('users.create') }}" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-700 transition">
        + Add User
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Email</th>
                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Role</th>
                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($users as $user)
                <tr>
                    <td class="px-6 py-4 font-medium text-gray-800">{{ $user->name }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $user->email }}</td>
                    <td class="px-6 py-4">
                        @foreach($user->roles as $role)
                            <span class="inline-block bg-slate-100 text-slate-700 text-xs font-semibold px-2 py-1 rounded-full capitalize">{{ $role->name }}</span>
                        @endforeach
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="{{ route('users.edit', $user) }}" class="text-blue-600 hover:underline text-sm">Edit</a>
                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Delete this user?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-400">No users found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $users->links() }}
</div>
@endsection
