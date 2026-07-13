<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Violation;
use Illuminate\Http\Request;

class ViolationController extends Controller
{
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
        $validated = $request->validate([
            'violation_date' => 'required|date',
            'type' => 'required|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        $member->violations()->create($validated);

        return redirect()->route('members.violations.index', $member)
            ->with('success', 'Violation recorded successfully.');
    }

    public function edit(Member $member, Violation $violation)
    {
        return view('violations.edit', compact('member', 'violation'));
    }

    public function update(Request $request, Member $member, Violation $violation)
    {
        $validated = $request->validate([
            'violation_date' => 'required|date',
            'type' => 'required|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        $violation->update($validated);

        return redirect()->route('members.violations.index', $member)
            ->with('success', 'Violation updated successfully.');
    }

    public function destroy(Member $member, Violation $violation)
    {
        $violation->delete();

        return redirect()->route('members.violations.index', $member)
            ->with('success', 'Violation deleted.');
    }
}
