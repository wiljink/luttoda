<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use App\Models\Member;
use App\Models\MemberDependent;
use App\Services\BenefitEligibilityService;
use App\Services\SavingsLedgerService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BenefitController extends Controller
{
    public function __construct(private BenefitEligibilityService $eligibility) {}

    public function index(Request $request)
    {
        $benefits = Benefit::with(['member', 'dependent'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest('claim_date')
            ->paginate(30);

        return view('benefits.index', compact('benefits'));
    }

    public function create()
    {
        $members = Member::active()->membersOnly()
            ->with('activeDependents')
            ->orderBy('firstname')
            ->get();

        return view('benefits.create', compact('members'));
    }

    /** JSON eligibility snapshot for the create form (member picker). */
    public function eligibility(Request $request)
    {
        $data = $request->validate([
            'member_id' => 'required|exists:members,id',
            'member_dependent_id' => 'nullable|exists:member_dependents,id',
            'year' => 'nullable|integer',
        ]);

        $member = Member::findOrFail($data['member_id']);
        $year = (int) ($data['year'] ?? now()->year);

        $summary = $this->eligibility->summary($member, $year);

        if (! empty($data['member_dependent_id'])) {
            $dependent = MemberDependent::find($data['member_dependent_id']);
            if ($dependent && $dependent->member_id === $member->id) {
                $summary['remaining_days'] = $this->eligibility->remainingDays($dependent, $year);
            }
        }

        return response()->json($summary);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'beneficiary_type' => 'required|in:member,dependent',
            'member_dependent_id' => 'nullable|required_if:beneficiary_type,dependent|exists:member_dependents,id',
            'benefit_type' => 'required|in:hospitalization,burial,sss',
            'claim_date' => 'required|date',
            'days' => 'nullable|integer|min:1',
            'remarks' => 'nullable|string',
        ]);

        $member = Member::findOrFail($validated['member_id']);
        $year = (int) date('Y', strtotime($validated['claim_date']));

        // 1. Member-level eligibility (category, standing, activity threshold).
        if (! $this->eligibility->isEligible($member, $year)) {
            throw ValidationException::withMessages([
                'member_id' => $this->eligibility->ineligibilityReason($member, $year),
            ]);
        }

        // 2. Resolve the beneficiary.
        $dependent = null;
        if ($validated['beneficiary_type'] === 'dependent') {
            $dependent = MemberDependent::find($validated['member_dependent_id']);
            if (! $dependent || $dependent->member_id !== $member->id) {
                throw ValidationException::withMessages([
                    'member_dependent_id' => 'That dependent does not belong to this member.',
                ]);
            }
            if (! $dependent->active) {
                throw ValidationException::withMessages([
                    'member_dependent_id' => 'That dependent is not active.',
                ]);
            }
        }

        // 3. Amount.
        if ($validated['benefit_type'] === 'hospitalization') {
            $days = (int) ($validated['days'] ?? 0);
            if ($days < 1) {
                throw ValidationException::withMessages(['days' => 'Days confined is required for hospitalization.']);
            }

            $beneficiary = $dependent ?? $member;
            $remaining = $this->eligibility->remainingDays($beneficiary, $year);
            if ($days > $remaining) {
                throw ValidationException::withMessages([
                    'days' => "Only {$remaining} benefit day(s) remain for this beneficiary in {$year}.",
                ]);
            }

            $amount = $days * Benefit::dailyRate($validated['beneficiary_type']);
        } else {
            $amount = Benefit::flatRate($validated['benefit_type']);
            $validated['days'] = null;
        }

        Benefit::create([
            'member_id' => $member->id,
            'beneficiary_type' => $validated['beneficiary_type'],
            'member_dependent_id' => $dependent?->id,
            'benefit_type' => $validated['benefit_type'],
            'claim_date' => $validated['claim_date'],
            'days' => $validated['days'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        return redirect()->route('benefits.index')
            ->with('success', 'Claim filed and awaiting approval.');
    }

    public function show(Benefit $benefit)
    {
        return view('benefits.show', compact('benefit'));
    }

    public function approve(Request $request, Benefit $benefit)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $benefit->update([
            'status' => $validated['status'],
            'processed_by' => auth()->id(),
            'processed_date' => now(),
        ]);

        return back()->with('success', "Benefit claim {$validated['status']}.");
    }

    public function release(Benefit $benefit)
    {
        if ($benefit->status !== 'approved') {
            return back()->withErrors(['benefit' => 'Kinahanglan naapproved una ang claim.']);
        }

        $benefit->update(['status' => 'released']);

        if ($benefit->benefit_type === 'sss') {
            // SSS benefit is a savings-type benefit -- release it through
            // the ledger so it shows up in the member's savings history
            // instead of just silently bumping the cached balance.
            app(SavingsLedgerService::class)->record(
                member: $benefit->member,
                date: today()->toDateString(),
                sourceType: 'benefit_release',
                txnType: 'deposit',
                amount: $benefit->amount,
                sourceable: $benefit,
                remarks: "SSS benefit released - claim #{$benefit->id}",
            );
        }

        return back()->with('success', 'Benefit na-release na.');
    }

    public function destroy(Benefit $benefit)
    {
        $benefit->delete();

        return redirect()->route('benefits.index')->with('success', 'Claim natangtang.');
    }
}
