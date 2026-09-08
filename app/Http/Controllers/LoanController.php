<?php

namespace App\Http\Controllers;

use App\Models\DailyDue;
use App\Models\FuelConsumption;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Setting;
use App\Services\BenefitEligibilityService;
use App\Services\DieselLoanService;
use App\Services\LoanPaymentService;
use App\Services\LoanPenaltyService;
use App\Services\LoanScheduleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    public function __construct(
        private BenefitEligibilityService $eligibility,
        private DieselLoanService $dieselLoans,
    ) {}

    public function index(Request $request)
    {
        $loans = Loan::with('member', 'schedules')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest('loan_date')
            ->paginate(30);

        return view('loans.index', compact('loans'));
    }

    public function create()
    {
        $members = Member::active()->orderBy('firstname')->get();
        $year = (int) now()->year;

        $ticketsA = (int) Setting::get('eligibility_tickets_a', 75);
        $ticketsB = (int) Setting::get('eligibility_tickets_b', 150);
        $litersReq = (float) Setting::get('eligibility_diesel_liters', 500);

        $ticketsByMember = DailyDue::whereYear('collection_date', $year)
            ->selectRaw('member_id, SUM(ticket_quantity) as total')
            ->groupBy('member_id')->pluck('total', 'member_id');
        $litersByMember = FuelConsumption::whereYear('consumption_date', $year)
            ->selectRaw('member_id, SUM(liters) as total')
            ->groupBy('member_id')->pluck('total', 'member_id');

        $eligibility = $members->mapWithKeys(function (Member $m) use ($ticketsByMember, $litersByMember, $ticketsA, $ticketsB, $litersReq, $year) {
            $tickets = (int) ($ticketsByMember[$m->id] ?? 0);
            $liters = (float) ($litersByMember[$m->id] ?? 0);

            return [$m->id => [
                'tickets' => $tickets,
                'liters' => $liters,
                'ok' => ! $m->isTerminated()
                    && ($tickets >= $ticketsA || $liters >= $litersReq || $tickets >= $ticketsB),
                'diesel_liters' => $this->dieselLoans->availableLiters($m, $year),
                'diesel_amount' => $this->dieselLoans->maxAmount($m, $year),
            ]];
        });

        $dieselFormula = [
            'factor' => $this->dieselLoans->literFactor(),
            'percentage' => $this->dieselLoans->percentage(),
        ];

        return view('loans.create', compact('members', 'eligibility', 'year', 'ticketsA', 'litersReq', 'dieselFormula'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'type' => 'required|in:cash,diesel',
            'amount' => 'required_if:type,cash|numeric|min:1',
            'interest_rate' => 'nullable|numeric|min:0',
            'term_months' => 'required|integer|min:1',
            'penalty_rate' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'purpose' => 'nullable|string',
        ]);

        $member = Member::findOrFail($validated['member_id']);
        $year = (int) now()->year;
        $isDiesel = $validated['type'] === 'diesel';

        if ($member->isTerminated()) {
            throw ValidationException::withMessages([
                'member_id' => 'Terminated members cannot avail a loan.',
            ]);
        }

        // Every loan (cash OR diesel) needs the yearly activity gate:
        // 75 tickets or 500 L diesel for the year.
        if (! $this->eligibility->meetsActivityThreshold($member, $year)) {
            throw ValidationException::withMessages([
                'member_id' => $this->eligibility->activityShortfall($member, $year),
            ]);
        }

        $litersBasis = null;

        if ($isDiesel) {
            // A diesel loan's amount is fully determined by the member's
            // un-borrowed diesel litres for the year (litres × 2 × 0.80).
            $litersBasis = $this->dieselLoans->availableLiters($member, $year);

            if ($litersBasis <= 0) {
                throw ValidationException::withMessages([
                    'member_id' => 'Member has no un-borrowed diesel litres for '.$year.'.',
                ]);
            }

            $validated['amount'] = $this->dieselLoans->amountForLiters($litersBasis);
        }

        Loan::create(array_merge($validated, [
            'liters_basis' => $litersBasis,
            'interest_rate' => $validated['interest_rate'] ?? 0,
            'penalty_rate' => $validated['penalty_rate'] ?? 2.00,
            'loan_date' => now(),
            'status' => 'pending',
        ]));

        return redirect()->route('loans.index')->with('success', match (true) {
            $isDiesel => 'Diesel loan submitted — ₱'.number_format($validated['amount'], 2)
                .' ('.rtrim(rtrim(number_format($litersBasis, 2), '0'), '.').' L).',
            default => 'Loan application submitted.',
        });
    }

    public function show(Loan $loan)
    {
        app(LoanPenaltyService::class)->assess($loan);

        $loan->load('payments.allocations', 'member', 'schedules.penalties');

        return view('loans.show', compact('loan'));
    }

    public function approve(Loan $loan)
    {
        $member = $loan->member;
        $year = (int) ($loan->loan_date?->year ?? now()->year);

        if ($member->isTerminated()) {
            return back()->withErrors(['loan' => 'Terminated members cannot be granted a loan.']);
        }

        // Re-check the yearly activity gate at approval time (the loan may
        // have sat pending, or activity may have been corrected since).
        if (! $this->eligibility->meetsActivityThreshold($member, $year)) {
            return back()->withErrors(['loan' => $this->eligibility->activityShortfall($member, $year)]);
        }

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

        app(LoanScheduleService::class)->generate($loan->fresh());

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
            'or_number' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:255',
        ]);

        // A loan payment is cash collected from the member — it settles the
        // loan only and never touches the member's savings ledger /
        // savings_balance. LoanPaymentService::pay() is the shared write
        // path (also used by the daily-collection importer).
        app(LoanPaymentService::class)->pay($loan, (float) $validated['amount'], [
            'or_number' => $validated['or_number'] ?? null,
            'payment_method' => $validated['payment_method'] ?? 'cash',
            'received_by' => auth()->id(),
        ]);

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
