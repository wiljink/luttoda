<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Member;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $loans = Loan::with('member')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest('loan_date')
            ->paginate(30);

        return view('loans.index', compact('loans'));
    }

    public function create()
    {
        $members = Member::active()->orderBy('firstname')->get();
        return view('loans.create', compact('members'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'amount' => 'required|numeric|min:1',
            'interest_rate' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'purpose' => 'nullable|string',
        ]);

        Loan::create(array_merge($validated, [
            'interest_rate' => $validated['interest_rate'] ?? 0,
            'loan_date' => now(),
            'status' => 'pending',
        ]));

        return redirect()->route('loans.index')
            ->with('success', 'Loan application submitted.');
    }

    public function show(Loan $loan)
    {
        $loan->load('payments', 'member');
        return view('loans.show', compact('loan'));
    }

    public function approve(Loan $loan)
    {
        $member = $loan->member;

        // Basic guard: member must have sufficient savings (e.g. 10% minimum)
        if ($member->savings_balance < $loan->amount * 0.10) {
            return back()->withErrors([
                'loan' => 'Member savings are insufficient to qualify.',
            ]);
        }

        $loan->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Loan approved.');
    }

    public function reject(Loan $loan)
    {
        $loan->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Loan rejected.');
    }

    public function addPayment(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        LoanPayment::create([
            'loan_id' => $loan->id,
            'payment_date' => now(),
            'amount' => $validated['amount'],
            'received_by' => auth()->id(),
        ]);

        $loan->decrement('balance', $validated['amount']);

        if ($loan->fresh()->balance <= 0) {
            $loan->update(['status' => 'paid']);
        } elseif ($loan->status === 'approved') {
            $loan->update(['status' => 'active']);
        }

        return back()->with('success', 'Payment recorded.');
    }

    public function destroy(Loan $loan)
    {
        if ($loan->payments()->exists()) {
            return back()->withErrors(['loan' => 'Cannot delete, payments have already been recorded.']);
        }

        $loan->delete();
        return redirect()->route('loans.index')->with('success', 'Loan deleted.');
    }
}
