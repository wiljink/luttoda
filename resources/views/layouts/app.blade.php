<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LUTTODA System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <div class="flex flex-col md:flex-row min-h-screen">
        <!-- Sidebar -->
        <div class="bg-slate-800 text-white w-full md:w-64 space-y-6 px-2 py-4 absolute inset-y-0 left-0 md:relative md:translate-x-0 transform -translate-x-full transition duration-200 ease-in-out">
            <div class="text-white flex items-center space-x-2 px-4">
                <span class="text-2xl font-extrabold tracking-wider">LUTTODA</span>
            </div>
            <nav class="space-y-1">
                <a href="{{ route('dashboard') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-slate-700 {{ request()->routeIs('dashboard') ? 'bg-slate-900 font-semibold' : '' }}">Dashboard</a>
                <a href="{{ route('members.index') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-slate-700 {{ request()->routeIs('members.*') ? 'bg-slate-900 font-semibold' : '' }}">Members</a>
                <a href="{{ route('daily-dues.index') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-slate-700 {{ request()->routeIs('daily-dues.*') ? 'bg-slate-900 font-semibold' : '' }}">Daily Dues</a>
                <a href="{{ route('fuel.index') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-slate-700 {{ request()->routeIs('fuel.*') ? 'bg-slate-900 font-semibold' : '' }}">Fuel Consumption</a>
                <a href="{{ route('loans.index') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-slate-700 {{ request()->routeIs('loans.*') ? 'bg-slate-900 font-semibold' : '' }}">Loans</a>
                <a href="{{ route('benefits.index') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-slate-700 {{ request()->routeIs('benefits.*') ? 'bg-slate-900 font-semibold' : '' }}">Benefits</a>
                <a href="{{ route('income-expenses.index') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-slate-700 {{ request()->routeIs('income-expenses.*') ? 'bg-slate-900 font-semibold' : '' }}">Income & Expenses</a>

                <div class="pt-4 border-t border-slate-700 text-xs px-4 text-slate-400 uppercase tracking-wider">Reports</div>
                <a href="{{ route('reports.daily') }}" class="block py-2 px-4 text-sm rounded transition duration-200 hover:bg-slate-700 {{ request()->routeIs('reports.daily') ? 'bg-slate-900 font-semibold' : '' }}">Daily</a>
                <a href="{{ route('reports.monthly') }}" class="block py-2 px-4 text-sm rounded transition duration-200 hover:bg-slate-700 {{ request()->routeIs('reports.monthly') ? 'bg-slate-900 font-semibold' : '' }}">Monthly</a>
                <a href="{{ route('reports.fuel') }}" class="block py-2 px-4 text-sm rounded transition duration-200 hover:bg-slate-700 {{ request()->routeIs('reports.fuel') ? 'bg-slate-900 font-semibold' : '' }}">Fuel Consumption</a>

                  <div class="pt-4 border-t border-slate-700 text-xs px-4 text-slate-400 uppercase tracking-wider">Settings</div>
                <a href="{{ route('tickets.index') }}" class="block py-2 px-4 text-sm rounded transition duration-200 hover:bg-slate-700 {{ request()->routeIs('dashboard') ? 'bg-slate-900 font-semibold' : '' }}">Tickets</a>

        </div>

        <!-- Main Content Area -->
        <div class="flex-1 p-6 md:p-10 overflow-hidden">
            <!-- Top Navbar: User Account -->
            <div class="flex items-center justify-end gap-4 mb-6">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-700">{{ Auth::user()->name ?? 'Guest' }}</span>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </button>
                </form>
            </div>

            <!-- Flash Message Handler -->
            @if(session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm" role="alert">
                    <p class="font-bold">Success!</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
                    <p class="font-bold">Please review the following errors:</p>
                    <ul class="list-disc pl-5 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Dynamic content for each page -->
            @yield('content')
        </div>
    </div>
</body>
</html>
