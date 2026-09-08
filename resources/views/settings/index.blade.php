@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Settings</h1>
        <p class="text-gray-500">Business rules used across dues, fuel, benefits and the annual dividend.</p>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        @foreach ($groups as $group => $items)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-3 bg-gray-50 border-b">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                        {{ $groupLabels[$group] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $group)) }}
                    </h2>
                </div>
                <div class="divide-y">
                    @foreach ($items as $item)
                        <div class="px-6 py-4 flex items-center gap-4">
                            <label for="setting-{{ $item->key }}" class="flex-1 text-sm text-gray-700">
                                {{ $item->label ?? \App\Http\Controllers\SettingController::humanize($item->key) }}
                                <span class="block text-xs text-gray-400 font-mono">{{ $item->key }}</span>
                            </label>
                            @if ($item->type === 'bool')
                                <input type="checkbox" id="setting-{{ $item->key }}"
                                       name="settings[{{ $item->key }}]" value="1"
                                       @checked(old("settings.$item->key", $item->cast()))
                                       class="rounded border-gray-300 text-slate-800 focus:ring-slate-700">
                            @else
                                <input type="{{ in_array($item->type, ['int', 'float']) ? 'number' : 'text' }}"
                                       @if ($item->type === 'float') step="0.01" @endif
                                       id="setting-{{ $item->key }}"
                                       name="settings[{{ $item->key }}]"
                                       value="{{ old("settings.$item->key", $item->value) }}"
                                       class="w-40 rounded-lg border-gray-300 text-sm text-right focus:border-slate-700 focus:ring-slate-700">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="bg-slate-800 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-700 transition">
                Save settings
            </button>
        </div>
    </form>
</div>
@endsection
