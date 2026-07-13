<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="relative overflow-hidden bg-white rounded-2xl shadow-xl shadow-emerald-900/10 border border-emerald-900/5">

        <!-- Jeepney-inspired top accent stripe -->
        <div class="h-2 w-full flex">
            <div class="flex-1 bg-emerald-700"></div>
            <div class="flex-1 bg-amber-500"></div>
            <div class="flex-1 bg-red-600"></div>
        </div>

        <div class="px-6 py-8 sm:px-10 sm:py-10">

            <div class="flex flex-col items-center mb-8">
                <div class="w-20 h-20 flex items-center justify-center mb-4 rounded-full bg-emerald-50 ring-4 ring-amber-400/30 p-3">
                    <img src="{{ asset('images/jeepney.png') }}" alt="Luttoda Jeepney Cooperative" class="w-full h-full object-contain drop-shadow-sm">
                </div>
                <h1 class="text-2xl font-bold text-emerald-900 tracking-tight">Welcome</h1>
                <p class="text-base text-gray-500 mt-1.5 text-center">
                    Sign in to <span class="font-semibold text-emerald-800">Luttoda Jeepney Cooperative</span>
                </p>
            </div>

            <!-- Validation error summary, written in plain language -->
            @if ($errors->any())
                <div class="mb-6 rounded-xl bg-red-50 border border-red-100 px-4 py-4 flex gap-3" role="alert" aria-live="assertive">
                    <svg class="w-6 h-6 text-red-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <div>
                        <p class="text-base text-red-700 font-medium leading-6">
                            @if ($errors->has('email') && str_contains($errors->first('email'), 'credentials') || $errors->has('password'))
                                That email or password isn't right. Please try again.
                            @else
                                {{ $errors->first() }}
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" x-data="{ submitting: false }" @submit="submitting = true" class="space-y-6">
                @csrf

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-base font-medium text-gray-800 mb-2">
                        Email address
                    </label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 pointer-events-none transition-colors group-focus-within:text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </span>
                        <input
                            id="email"
                            class="block w-full pl-12 pr-4 py-3.5 text-base rounded-xl border-gray-200 bg-gray-50 shadow-sm transition focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 focus:ring-2 {{ $errors->has('email') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                            type="email"
                            name="email"
                            placeholder="you@example.com"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        />
                    </div>
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div x-data="{ show: false }">
                    <label for="password" class="block text-base font-medium text-gray-800 mb-2">
                        Password
                    </label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 pointer-events-none transition-colors group-focus-within:text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </span>
                        <input
                            id="password"
                            class="block w-full pl-12 pr-14 py-3.5 text-base rounded-xl border-gray-200 bg-gray-50 shadow-sm transition focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 focus:ring-2 {{ $errors->has('password') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                            :type="show ? 'text' : 'password'"
                            name="password"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                            aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                        />
                        <!-- Big, easy-to-tap show/hide button -->
                        <button
                            type="button"
                            @click="show = !show"
                            class="absolute inset-y-0 right-0 flex items-center justify-center w-14 text-gray-400 hover:text-emerald-600 active:text-emerald-700 focus:outline-none"
                            :aria-label="show ? 'Hide password' : 'Show password'"
                            :aria-pressed="show"
                        >
                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="display: none;" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <label for="remember_me" class="inline-flex items-center cursor-pointer select-none py-2">
                        <input id="remember_me" type="checkbox" class="w-5 h-5 rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500 focus:ring-2" name="remember">
                        <span class="ms-2.5 text-base text-gray-700">Keep me signed in</span>
                    </label>
                </div>

                @if (Route::has('password.request'))
                    <div>
                        <a class="inline-block text-base font-medium text-emerald-700 hover:text-emerald-800 transition-colors py-1" href="{{ route('password.request') }}">
                            Forgot your password?
                        </a>
                    </div>
                @endif

                <!-- Large, easy-to-tap submit button -->
                <button
                    type="submit"
                    :disabled="submitting"
                    :class="submitting ? 'opacity-70 cursor-not-allowed' : 'hover:bg-emerald-800'"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-transparent bg-emerald-700 px-4 py-4 text-base font-semibold text-white shadow-md shadow-emerald-900/20 transition focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"
                >
                    <svg x-show="submitting" class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true" style="display: none;">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Signing in…' : 'Log in'"></span>
                </button>

                <p class="text-center text-sm text-gray-400 mt-6">
                    Need help signing in? Contact your cooperative administrator.
                </p>
            </form>

        </div>

        <!-- Jeepney-inspired bottom accent stripe -->
        <div class="h-2 w-full flex">
            <div class="flex-1 bg-red-600"></div>
            <div class="flex-1 bg-amber-500"></div>
            <div class="flex-1 bg-emerald-700"></div>
        </div>
    </div>
</x-guest-layout>
