<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Violation;
use App\Services\MemberSanctionService;
use Illuminate\Http\Request;

class ViolationController extends Controller
{
    public function __construct(private MemberSanctionService $sanctions) {}

    private const RULES = [
        'violation_date' => 'required|date',
        'type' => 'required|string|max:150',
        'notes' => 'nullable|string|max:1000',
        'sanction' => 'nullable|in:suspension,termination,dismembership',
        'sanction_until' => 'nullable|date|required_if:sanction,suspension|after:today',
    ];

    /**
     * List all violations recorded for a specific member.
     */
    public function index(Member $member)
    {
        $violations = $member->violations()
            ->orderByDesc('violation_date')
            ->paginate(20);

        return view('violations.index', compact('member', 'violations'));
    }

    public function create(Member $member)
    {
        return view('violations.create', compact('member'));
    }

    public function store(Request $request, Member $member)
    {
        $validated = $request->validate(self::RULES);

        $violation = $member->violations()->create($validated);
        $this->sanctions->apply($violation->fresh('member'));

        return redirect()->route('members.violations.index', $member)
            ->with('success', $this->flash('recorded', $violation));
    }

    public function edit(Member $member, Violation $violation)
    {
        return view('violations.edit', compact('member', 'violation'));
    }

    public function update(Request $request, Member $member, Violation $violation)
    {
        $validated = $request->validate(self::RULES);

        $violation->update($validated);
        $this->sanctions->apply($violation->fresh('member'));

        return redirect()->route('members.violations.index', $member)
            ->with('success', $this->flash('updated', $violation));
    }

    public function destroy(Member $member, Violation $violation)
    {
        $violation->delete();

        return redirect()->route('members.violations.index', $member)
            ->with('success', 'Violation deleted.');
    }

    /**
     * Success message that also spells out the standing change when the
     * violation carries a sanction.
     */
    private function flash(string $verb, Violation $violation): string
    {
        return match ($violation->sanction) {
            'suspension' => "Violation {$verb}. Member suspended until "
                .$violation->sanction_until?->format('M j, Y').'.',
            'termination', 'dismembership' => "Violation {$verb}. Member marked as terminated.",
            default => "Violation {$verb} successfully.",
        };
    }
}
