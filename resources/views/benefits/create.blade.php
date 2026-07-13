@extends('layouts.app')

@section('title', 'File a Claim')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">

    <p class="text-xs font-semibold tracking-widest text-amber-600 uppercase mb-1">Member Benefits</p>
    <h1 class="text-2xl font-bold text-slate-900 mb-6">File a Claim</h1>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('benefits.store') }}" class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label for="member_id" class="block text-sm font-medium text-slate-700 mb-1.5">Member</label>
            <select id="member_id" name="member_id" required
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-[#1B3A4B] focus:ring-[#1B3A4B]">
                <option value="">Select a member</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected(old('member_id') == $member->id)>
                        {{ $member->firstname }} {{ $member->lastname }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Benefit type</label>
            <div class="grid grid-cols-3 gap-3">
                @foreach ([
                    'hospitalization' => 'Hospitalization',
                    'burial' => 'Burial',
                    'sss' => 'SSS',
                ] as $value => $label)
                    <label class="relative flex cursor-pointer items-center justify-center rounded-lg border border-slate-300 py-3 text-sm font-medium text-slate-700 hover:border-[#1B3A4B] has-[:checked]:border-[#1B3A4B] has-[:checked]:bg-[#1B3A4B]/5 has-[:checked]:text-[#1B3A4B]">
                        <input type="radio" name="benefit_type" value="{{ $value }}" class="sr-only" {{ old('benefit_type') === $value ? 'checked' : '' }} required onchange="toggleDaysField()">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label for="claim_date" class="block text-sm font-medium text-slate-700 mb-1.5">Claim date</label>
            <input type="date" id="claim_date" name="claim_date" value="{{ old('claim_date', date('Y-m-d')) }}" required
                   class="w-full rounded-lg border-slate-300 text-sm focus:border-[#1B3A4B] focus:ring-[#1B3A4B]">
        </div>

        <div id="days_field">
            <label for="days" class="block text-sm font-medium text-slate-700 mb-1.5">Days confined</label>
            <input type="number" id="days" name="days" min="1" value="{{ old('days', 1) }}"
                   class="w-full rounded-lg border-slate-300 text-sm focus:border-[#1B3A4B] focus:ring-[#1B3A4B]">
            <p class="mt-1 text-xs text-slate-400">Only applies to hospitalization claims.</p>
        </div>

        <div>
            <label for="remarks" class="block text-sm font-medium text-slate-700 mb-1.5">Remarks <span class="text-slate-400 font-normal">(optional)</span></label>
            <textarea id="remarks" name="remarks" rows="3"
                      class="w-full rounded-lg border-slate-300 text-sm focus:border-[#1B3A4B] focus:ring-[#1B3A4B]">{{ old('remarks') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('benefits.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</a>
            <button type="submit"
                    class="bg-[#1B3A4B] hover:bg-[#15303e] text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors">
                Submit claim
            </button>
        </div>
    </form>
</div>

<script>
    function toggleDaysField() {
        const type = document.querySelector('input[name="benefit_type"]:checked')?.value;
        document.getElementById('days_field').style.display = (type === 'hospitalization') ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', toggleDaysField);
</script>
@endsection
