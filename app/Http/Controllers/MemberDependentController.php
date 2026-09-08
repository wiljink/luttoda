<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberDependent;
use Illuminate\Http\Request;

class MemberDependentController extends Controller
{
    private const RULES = [
        'name' => 'required|string|max:150',
        'relationship' => 'required|string|max:50',
        'birthdate' => 'nullable|date|before_or_equal:today',
        'active' => 'nullable|boolean',
    ];

    public function store(Request $request, Member $member)
    {
        $data = $request->validate(self::RULES);
        $data['active'] = $request->boolean('active', true);

        $member->dependents()->create($data);

        return redirect()->route('members.show', $member)
            ->with('success', 'Dependent added.');
    }

    public function update(Request $request, Member $member, MemberDependent $dependent)
    {
        abort_unless($dependent->member_id === $member->id, 404);

        $data = $request->validate(self::RULES);
        $data['active'] = $request->boolean('active');

        $dependent->update($data);

        return redirect()->route('members.show', $member)
            ->with('success', 'Dependent updated.');
    }

    public function destroy(Member $member, MemberDependent $dependent)
    {
        abort_unless($dependent->member_id === $member->id, 404);

        $dependent->delete();

        return redirect()->route('members.show', $member)
            ->with('success', 'Dependent removed.');
    }
}
