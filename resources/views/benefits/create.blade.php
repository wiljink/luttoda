@extends('layouts.app')

@section('title', 'File a Claim')

@section('content')
@php
    $memberData = $members->mapWithKeys(fn ($m) => [$m->id => [
        'name' => $m->firstname.' '.$m->lastname,
        'dependents' => $m->activeDependents->map(fn ($d) => ['id' => $d->id, 'name' => $d->name.' ('.$d->relationship.')'])->values(),
    ]]);
@endphp
<div class="max-w-2xl mx-auto px-4 py-8"
     x-data="benefitForm(@js($memberData), '{{ route('benefits.eligibility') }}')">

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
            <select id="member_id" name="member_id" required x-model="memberId" @change="refresh()"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-[#1B3A4B] focus:ring-[#1B3A4B]">
                <option value="">Select a member</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected(old('member_id') == $member->id)>
                        {{ $member->firstname }} {{ $member->lastname }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-400">Only active members (non-members are not eligible for benefits).</p>
        </div>

        {{-- Eligibility snapshot --}}
        <template x-if="summary">
            <div class="rounded-lg px-4 py-3 text-sm"
                 :class="summary.eligible ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'">
                <p x-show="summary.eligible">
                    Eligible for <span x-text="summary.year"></span> —
                    <span x-text="summary.tickets_ytd"></span> tickets,
                    <span x-text="Number(summary.liters_ytd).toFixed(2)"></span> L diesel.
                    <span x-text="summary.remaining_days"></span> of <span x-text="summary.max_days"></span> benefit days left.
                </p>
                <p x-show="!summary.eligible" x-text="summary.reason"></p>
            </div>
        </template>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Beneficiary</label>
            <div class="flex gap-4 text-sm">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="beneficiary_type" value="member" x-model="beneficiaryType" @change="refresh()" required> Member (self)
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="beneficiary_type" value="dependent" x-model="beneficiaryType" @change="refresh()"> Dependent
                </label>
            </div>
            <div x-show="beneficiaryType === 'dependent'" x-cloak class="mt-2">
                <select name="member_dependent_id" x-model="dependentId" @change="refresh()"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-[#1B3A4B] focus:ring-[#1B3A4B]">
                    <option value="">Select a dependent</option>
                    <template x-for="d in dependents" :key="d.id">
                        <option :value="d.id" x-text="d.name"></option>
                    </template>
                </select>
                <p x-show="memberId && dependents.length === 0" class="mt-1 text-xs text-rose-500">
                    This member has no active dependents. Add one from their profile first.
                </p>
            </div>
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
                        <input type="radio" name="benefit_type" value="{{ $value }}" class="sr-only" x-model="benefitType" {{ old('benefit_type') === $value ? 'checked' : '' }} required>
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

        <div x-show="benefitType === 'hospitalization'" x-cloak>
            <label for="days" class="block text-sm font-medium text-slate-700 mb-1.5">Days confined</label>
            <input type="number" id="days" name="days" min="1" value="{{ old('days', 1) }}"
                   class="w-full rounded-lg border-slate-300 text-sm focus:border-[#1B3A4B] focus:ring-[#1B3A4B]">
            <p class="mt-1 text-xs text-slate-400">
                Paid per day: ₱{{ number_format(\App\Models\Benefit::dailyRate('member'), 0) }} (member) /
                ₱{{ number_format(\App\Models\Benefit::dailyRate('dependent'), 0) }} (dependent).
            </p>
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
    function benefitForm(memberData, eligibilityUrl) {
        return {
            memberData,
            eligibilityUrl,
            memberId: '{{ old('member_id') }}',
            beneficiaryType: '{{ old('beneficiary_type', 'member') }}',
            dependentId: '{{ old('member_dependent_id') }}',
            benefitType: '{{ old('benefit_type', 'hospitalization') }}',
            summary: null,
            get dependents() {
                return this.memberData[this.memberId]?.dependents ?? [];
            },
            init() {
                if (this.memberId) this.refresh();
            },
            async refresh() {
                if (!this.memberId) { this.summary = null; return; }
                const params = new URLSearchParams({ member_id: this.memberId });
                if (this.beneficiaryType === 'dependent' && this.dependentId) {
                    params.append('member_dependent_id', this.dependentId);
                }
                try {
                    const res = await fetch(`${this.eligibilityUrl}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    this.summary = res.ok ? await res.json() : null;
                } catch (e) {
                    this.summary = null;
                }
            },
        };
    }
</script>
@endsection
