<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use App\Models\DailyDue;
use App\Models\FuelConsumption;
use App\Models\IncomeExpense;
use App\Models\Loan;
use App\Models\Member;
use App\Services\Reports\LuttodaReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(protected LuttodaReportService $reports) {}

    public function daily(Request $request)
    {
        $date = $request->get('date', today()->toDateString());

        $duesRaw = DailyDue::with(['collector', 'tickets'])
            ->onDate($date)
            ->get();

        // Per collector + route rollup. Variance = a due whose ticket_quantity
        // doesn't match how many Ticket rows actually exist for it.
        $collectionReport = $duesRaw
            ->groupBy(fn ($due) => $due->route.'|'.($due->collector->name ?? 'Unassigned'))
            ->map(function ($group) {
                $first = $group->first();

                return (object) [
                    'route' => $first->route,
                    'collector_name' => $first->collector->name ?? 'Unassigned',
                    'tickets_collected' => $group->sum('ticket_quantity'),
                    'total_collected' => $group->sum('amount_paid'),
                    'total_to_savings' => $group->sum('savings_share'),
                    'total_to_rebate_pool' => $group->sum('rebate_share'),
                    'total_to_association_fund' => $group->sum('association_share'),
                    'variance_count' => $group->filter(
                        fn ($due) => $due->tickets->count() !== $due->ticket_quantity
                    )->count(),
                ];
            })
            ->values();

        $duesIncome = $duesRaw->sum('amount_paid');
        $otherIncome = IncomeExpense::whereDate('transaction_date', $date)->income()->sum('amount');
        $expenses = IncomeExpense::whereDate('transaction_date', $date)->expense()->sum('amount');

        // FIXED: benefits.status is an enum('pending','approved','released','rejected') --
        // 'paid' is not a valid value and always matched zero rows. Use 'released'.
        $benefitsPaid = Benefit::whereDate('processed_date', $date)->where('status', 'released')->sum('amount');

        // Reuses Loan::scopeActive() ('approved'/'active'). Swap if "released"
        // should mean something narrower than that.
        $loansReleased = Loan::whereDate('loan_date', $date)->active()->sum('amount');

        $cashPosition = (object) [
            'dues_income' => $duesIncome,
            'other_income' => $otherIncome,
            'total_cash_in' => $duesIncome + $otherIncome,
            'total_expenses' => $expenses,
            'benefits_paid' => $benefitsPaid,
            'loans_released' => $loansReleased,
            'total_cash_out' => $expenses + $benefitsPaid + $loansReleased,
            'net_cash_position' => ($duesIncome + $otherIncome) - ($expenses + $benefitsPaid + $loansReleased),
        ];

        return view('reports.daily', compact('collectionReport', 'cashPosition', 'date'));
    }

    public function monthly(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));

        $dues = DailyDue::whereRaw("DATE_FORMAT(collection_date, '%Y-%m') = ?", [$month])->get();
        $income = IncomeExpense::income()->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$month])->sum('amount');
        $expense = IncomeExpense::expense()->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$month])->sum('amount');

        return view('reports.monthly', compact('dues', 'income', 'expense', 'month'));
    }

    public function memberLedger(Member $member)
    {
        return view('reports.member-ledger', [
            'member' => $member,
            'ledger' => $member->savingsLedgers()->orderBy('date')->orderBy('ledger_id')->get(),
            'loans' => $member->loans()->with('payments')->get(),
            'benefits' => $member->benefits()->get(),
            'fuel' => $member->fuelConsumptions()->get(),
            'dues' => $member->dailyDues()->orderBy('collection_date')->get(),
        ]);
    }

    /**
     * PDF export of a single member's savings ledger.
     * Was previously missing entirely -- reports.member.export
     * routed to this method but it didn't exist on the controller.
     */
    public function exportMemberLedger(Member $member)
    {
        $ledger = $member->savingsLedgers()->orderBy('date')->orderBy('ledger_id')->get();

        $pdf = Pdf::loadView('reports.pdf.member-ledger', [
            'member' => $member,
            'ledger' => $ledger,
        ]);

        return $pdf->download("member-ledger-{$member->member_no}.pdf");
    }

    /**
     * PDF statement of account for one member, for a calendar year
     * (defaults to the current year; ?year=YYYY to pick another).
     * Any member's statement can be printed on request.
     */
    public function exportMemberStatement(Member $member, Request $request)
    {
        $year = (int) $request->get('year', now()->year);

        $data = $this->reports->memberStatementOfAccount($member->id, $year);

        $pdf = Pdf::loadView('reports.pdf.member-statement', $data + [
            'generatedAt' => now(),
        ]);

        return $pdf->download("statement-{$member->member_no}-{$year}.pdf");
    }

    public function fuelConsumption(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $records = FuelConsumption::with('member')
            ->whereBetween('consumption_date', [$from, $to])
            ->get();

        return view('reports.fuel', compact('records', 'from', 'to'));
    }

    public function export($type, Request $request)
    {
        $date = $request->get('date', today()->toDateString());
        $data = match ($type) {
            'daily' => ['dues' => DailyDue::with('member')->onDate($date)->get()->groupBy('route')],
            'fuel' => ['records' => FuelConsumption::with('member')->whereDate('consumption_date', $date)->get()],
            default => [],
        };

        $pdf = Pdf::loadView("reports.pdf.{$type}", array_merge($data, ['date' => $date]));

        return $pdf->download("luttoda-{$type}-{$date}.pdf");
    }

    /**
     * View-based page for the Rebate Pool report + a button to actually
     * release the pending amount. Separate from rebatePool() below,
     * which stays as the JSON API endpoint.
     */
    public function rebatePoolPage(Request $request)
    {
        $year = (int) $request->get('year', now()->year);

        $members = $this->reports->rebatePoolReport($year);
        $totalPending = $this->reports->rebatePoolTotalPending($year);

        return view('reports.rebate-pool', compact('members', 'totalPending', 'year'));
    }

    /**
     * View-based page for the Annual Dividend Rebate + a Release button.
     * The dividend is (member's diesel liters for the year x diesel
     * price) / 2, credited to savings.
     */
    public function dividendRebatePage(Request $request)
    {
        $year = (int) $request->get('year', now()->year);

        $members = $this->reports->dividendRebateReport($year);
        $totalPending = $this->reports->dividendRebateTotalPending($year);

        return view('reports.dividend-rebate', compact('members', 'totalPending', 'year'));
    }

    /**
     * View-based Collections / Rental Income report: income_expenses
     * income grouped by category for a date range.
     */
    public function collectionsIncomePage(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        return view('reports.collections-income', array_merge(
            $this->reports->collectionsIncomeReport($from, $to),
            ['from' => $from, 'to' => $to],
        ));
    }

    public function collectionsIncome(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $from = $data['start_date'] ?? now()->startOfMonth()->toDateString();
        $to = $data['end_date'] ?? now()->toDateString();

        $report = $this->reports->collectionsIncomeReport($from, $to);
        unset($report['transactions']); // keep the JSON payload small

        return response()->json($report);
    }

    /**
     * View-based Annual Savings Return page + a Release button. Pays
     * back ₱35 savings + ₱7.50 share per ticket in cash, once a year.
     */
    public function savingsReturnPage(Request $request)
    {
        $year = (int) $request->get('year', now()->year);

        return view('reports.savings-return', [
            'members' => $this->reports->savingsReturnReport($year),
            'totalPending' => $this->reports->savingsReturnTotalPending($year),
            'returnDate' => $this->reports->savingsReturnDate($year),
            'year' => $year,
        ]);
    }

    public function savingsReturn(Request $request): JsonResponse
    {
        $data = $request->validate(['year' => 'nullable|integer']);
        $year = (int) ($data['year'] ?? now()->year);

        return response()->json([
            'year' => $year,
            'members' => $this->reports->savingsReturnReport($year),
            'total_pending' => $this->reports->savingsReturnTotalPending($year),
        ]);
    }

    public function releaseSavingsReturn(Request $request, int $year)
    {
        $paid = $this->reports->markSavingsReturnReleased($year);

        if ($request->wantsJson()) {
            return response()->json(['year' => $year, 'members_paid' => $paid]);
        }

        return back()->with('success', "Annual savings return released for {$year} ({$paid} member(s) paid).");
    }

    public function dividendRebate(Request $request): JsonResponse
    {
        $data = $request->validate(['year' => 'nullable|integer']);
        $year = (int) ($data['year'] ?? now()->year);

        return response()->json([
            'year' => $year,
            'members' => $this->reports->dividendRebateReport($year),
            'total_pending' => $this->reports->dividendRebateTotalPending($year),
        ]);
    }

    public function releaseDividend(Request $request, int $year)
    {
        $updated = $this->reports->markDividendReleased($year);

        if ($request->wantsJson()) {
            return response()->json(['year' => $year, 'records_updated' => $updated]);
        }

        return back()->with('success', "Dividend rebate released for {$year} ({$updated} member(s) credited).");
    }

    /**
     * View-based page for the Association Fund report. Separate from
     * associationFund() below, which stays as the JSON API endpoint.
     */
    public function associationFundPage(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        return view('reports.association-fund', $this->reports->associationFundReport($from, $to));
    }

    /**
     * View-based page for the Coop Deposit report. Separate from
     * coopDeposit() below, which stays as the JSON API endpoint.
     */
    public function coopDepositPage(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        return view('reports.coop-deposit', $this->reports->coopDepositReport($from, $to));
    }

    public function ledgerSearch(Request $request)
    {
        $query = $request->get('q');

        $members = $query
            ? Member::where('firstname', 'like', "%{$query}%")
                ->orWhere('lastname', 'like', "%{$query}%")
                ->orWhere('member_no', 'like', "%{$query}%")
                ->orderBy('lastname')
                ->orderBy('firstname')
                ->get()
            : collect();

        return view('reports.ledger-search', compact('members', 'query'));
    }

    /*
    |--------------------------------------------------------------------
    | Luttoda reports (JSON) — merged in from LuttodaReportController
    |--------------------------------------------------------------------
    */

    // 1. Daily Collection Report
    public function dailyCollection(Request $request): JsonResponse
    {
        $data = $request->validate(['date' => 'required|date']);

        return response()->json(
            $this->reports->dailyCollectionReport($data['date'])
        );
    }

    // 2. Daily Cash Position / Closing Report
    public function dailyCashPosition(Request $request): JsonResponse
    {
        $data = $request->validate(['date' => 'required|date']);

        return response()->json(
            $this->reports->dailyCashPosition($data['date'])
        );
    }

    // 3. Diesel / Fuel Consumption Report (JSON)
    // NOTE: renamed from fuelConsumption() to avoid colliding with the
    // existing view-based fuelConsumption() above.
    public function fuelConsumptionReport(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        return response()->json(
            $this->reports->fuelConsumptionReport($data['start_date'], $data['end_date'])
        );
    }

    // 4. Member Savings Ledger
    public function memberSavingsLedger(Request $request, int $memberId): JsonResponse
    {
        return response()->json(
            $this->reports->memberSavingsLedger($memberId)
        );
    }

    // 5. Member Statement of Account
    public function memberStatement(Request $request, int $memberId): JsonResponse
    {
        $data = $request->validate(['year' => 'required|integer']);

        return response()->json(
            $this->reports->memberStatementOfAccount($memberId, $data['year'])
        );
    }

    // 6. Rebate Pool Report (per member + association-wide total)
    public function rebatePool(Request $request): JsonResponse
    {
        $data = $request->validate(['year' => 'required|integer']);

        return response()->json([
            'members' => $this->reports->rebatePoolReport($data['year']),
            'total_pending' => $this->reports->rebatePoolTotalPending($data['year']),
        ]);
    }

    // 7. Association Fund Report
    public function associationFund(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        return response()->json(
            $this->reports->associationFundReport($data['start_date'], $data['end_date'])
        );
    }

    // 8. Coop Deposit Report
    public function coopDeposit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        return response()->json(
            $this->reports->coopDepositReport($data['start_date'], $data['end_date'])
        );
    }

    // 9. Income Statement (P&L)
    public function incomeStatement(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        return response()->json(
            $this->reports->incomeStatement($data['start_date'], $data['end_date'])
        );
    }

    // 10. Rental Income Report
    public function rentalIncome(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        return response()->json(
            $this->reports->rentalIncomeReport($data['start_date'], $data['end_date'])
        );
    }

    // 11. Benefits Utilization Report (detail + summary)
    public function benefitsUtilization(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        return response()->json([
            'details' => $this->reports->benefitsUtilizationReport($data['start_date'], $data['end_date']),
            'summary' => $this->reports->benefitsSummaryByType($data['start_date'], $data['end_date']),
        ]);
    }

    // 12. Loan Ledger / Aging Report
    public function loanLedgerAging(): JsonResponse
    {
        return response()->json(
            $this->reports->loanLedgerAgingReport()
        );
    }

    // 13. Monthly Summary Report
    public function monthlySummary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_month' => 'required|date',
            'end_month' => 'required|date|after_or_equal:start_month',
        ]);

        return response()->json(
            $this->reports->monthlySummaryReport($data['start_month'], $data['end_month'])
        );
    }

    // 14. Annual Rebate Release Report (read-only)
    public function annualRebateRelease(Request $request): JsonResponse
    {
        $data = $request->validate(['year' => 'required|integer']);

        return response()->json(
            $this->reports->annualRebateReleaseReport($data['year'])
        );
    }

    // 14b. Marks the rebate pool as released for the year.
    // FIXED: was validating 'year' from the request body, but the route
    // (annual-rebate-release/{year}/release) passes it as a route
    // segment -- that validation could never pass. Bind $year directly
    // instead. Also now redirects back with a flash message for the
    // HTML "Release" button on reports.rebate-pool.page, while still
    // returning JSON for API/AJAX callers that ask for it.
    public function releaseRebatePool(Request $request, int $year)
    {
        $updated = $this->reports->markRebatePoolReleased($year);

        if ($request->wantsJson()) {
            return response()->json([
                'year' => $year,
                'records_updated' => $updated,
            ]);
        }

        return back()->with('success', "Rebate pool released for {$year} ({$updated} member(s) updated).");
    }

    // 15. Simplified Trial Balance
    public function trialBalance(): JsonResponse
    {
        return response()->json(
            $this->reports->simplifiedTrialBalance()
        );
    }
}
