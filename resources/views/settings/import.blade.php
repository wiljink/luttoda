@extends('layouts.app')

@section('title', 'Import Data')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Import Data</h1>
        <p class="text-gray-500">Upload the LUTTODA Excel templates to bulk-load records. The upload <strong>saves the rows</strong> unless you tick <strong>Preview only</strong>, which just checks the file.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
            <p class="font-bold">Done</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if ($errors->has('file'))
        <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
            {{ $errors->first('file') }}
        </div>
    @endif

    @php($importErrors = session('import_errors'))
    @if ($importErrors)
        <div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg p-4">
            <p class="font-semibold text-amber-800 mb-2">Skipped rows</p>
            <ul class="text-sm text-amber-700 space-y-1 max-h-60 overflow-y-auto">
                @foreach ($importErrors as $err)
                    <li>Row {{ $err['row'] }} — {{ $err['message'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        @foreach ([
            'members' => ['Members', 'first name, last name, plate number, route, member no, operator name, category, contact number, address, date joined, status', 'members'],
            'daily_collection' => ['Daily Collection', 'name, plate number, route, number of tickets, total amount (₱50/ticket — system assigns the numbers), date, loans, benefit claim, alkansiya, diesel liters, remarks', 'daily_dues (+ tickets, + fuel, + alkansiya, + loan payment)'],
            'expenses' => ['Expenses', 'control number, date, name, particulars, amount, remarks', 'income & expenses (expense)'],
            'rental' => ['Rental Income', 'business name, date, route, rental amount, parking fee, dispatcher rental, rental type', 'income & expenses (income)'],
        ] as $key => [$title, $columns, $target])
            <form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data"
                  class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 flex flex-col">
                @csrf
                <input type="hidden" name="template" value="{{ $key }}">
                <h2 class="font-semibold text-gray-800">{{ $title }}</h2>
                <p class="text-xs text-gray-400 mt-1 mb-3">Columns: {{ $columns }}</p>
                <p class="text-xs text-gray-500 mb-3">→ {{ $target }}</p>

                <input type="file" name="file" accept=".xlsx" required
                       class="text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded file:border-0 file:bg-blue-50 file:text-blue-700 file:font-semibold hover:file:bg-blue-100 mb-3">

                <label class="flex items-center gap-2 text-sm text-gray-600 mb-1">
                    <input type="checkbox" name="preview" value="1" class="rounded border-gray-300">
                    Preview only — check the file, save nothing
                </label>
                <p class="text-xs text-gray-400 mb-4">Leave unchecked to actually import the rows.</p>

                <button type="submit" class="mt-auto px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-medium hover:bg-slate-700 transition">
                    Upload
                </button>
            </form>
        @endforeach
    </div>

    <h2 class="text-lg font-semibold text-gray-800 mt-10 mb-3">Recent imports</h2>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">When</th>
                    <th class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Template</th>
                    <th class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">File</th>
                    <th class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">By</th>
                    <th class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase text-right">Imported</th>
                    <th class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase text-right">Skipped</th>
                    <th class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Mode</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-2 text-gray-600">{{ $log->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-2 text-gray-800">{{ $log->template_label }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $log->filename }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $log->user->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-right font-medium text-green-700">{{ $log->imported }}</td>
                        <td class="px-4 py-2 text-right {{ $log->skipped ? 'text-amber-700' : 'text-gray-400' }}">{{ $log->skipped }}</td>
                        <td class="px-4 py-2">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $log->preview ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700' }}">
                                {{ $log->preview ? 'preview' : 'committed' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No imports yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
