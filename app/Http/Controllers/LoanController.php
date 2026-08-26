<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Services\SavingsLedgerService;
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

        // Basic guard: member must have sufficient savings (e.g. 2% minimum)
        if ($member->savings_balance < $loan->amount * 0.02) {
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

        $payment = LoanPayment::create([
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

        // ASSUMPTION -- UNCONFIRMED: this writes a 'loan_deduction'
        // withdrawal to the savings ledger, treating loan payments as if
        // they come OUT of the member's savings balance.
        //
        // But approve() only checks savings_balance as a collateral/
        // eligibility gate (>= 2% of loan amount) -- it never actually
        // reserves or touches that money. A loan payment here looks like a
        // cash payment collected FROM the member (received_by => collector),
        // which has nothing to do with their savings balance.
        //
        // If that reading is correct, DELETE the block below entirely --
        // loan payments should not touch savings_ledger at all.
        //
        // Keep this block only if loan repayments are actually meant to be
        // auto-deducted from savings (e.g. a payroll/dues-deduction style
        // arrangement instead of cash-in-hand).
        app(SavingsLedgerService::class)->record(
            member: $loan->member,
            date: $payment->payment_date,
            sourceType: 'loan_deduction',
            txnType: 'withdrawal',
            amount: $payment->amount,
            sourceable: $payment,
            remarks: "Loan payment - Loan #{$loan->id}",
        );

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
