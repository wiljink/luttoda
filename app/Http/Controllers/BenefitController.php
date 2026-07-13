<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use App\Models\Member;
use Illuminate\Http\Request;

class BenefitController extends Controller
{
    public function index(Request $request)
    {
        $benefits = Benefit::with('member')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest('claim_date')
            ->paginate(30);

        return view('benefits.index', compact('benefits'));
    }

    public function create()
    {
        $members = Member::active()->orderBy('firstname')->get();
        return view('benefits.create', compact('members'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'benefit_type' => 'required|in:hospitalization,burial,sss',
            'claim_date' => 'required|date',
            'days' => 'nullable|integer|min:1',
            'remarks' => 'nullable|string',
        ]);

        $amount = match ($validated['benefit_type']) {
            'hospitalization' => ($validated['days'] ?? 1) * Benefit::RATES['hospitalization'],
            'burial' => Benefit::RATES['burial'],
            'sss' => Benefit::RATES['sss'],
        };

        Benefit::create(array_merge($validated, [
            'amount' => $amount,
            'status' => 'pending',
        ]));

        return redirect()->route('benefits.index')
            ->with('success', 'Naisumite ang claim, naghulat og approval.');
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
            $benefit->member->increment('savings_balance', $benefit->amount);
        }

        return back()->with('success', 'Benefit na-release na.');
    }

    public function destroy(Benefit $benefit)
    {
        $benefit->delete();
        return redirect()->route('benefits.index')->with('success', 'Claim natangtang.');
    }
}
