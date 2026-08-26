<?php

namespace App\Services\Reports;

use App\Models\Benefit;
use App\Models\DailyDue;
use App\Models\FuelConsumption;
use App\Models\IncomeExpense;
use App\Models\Loan;
use App\Models\Member;
use App\Models\SavingsLedger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LuttodaReportService
{
    // income_expenses.category values that represent rental income.
    // There is no single 'rental' category in the schema -- these three
    // ENUM values are what count as "rental income" for this report.
    private const RENTAL_CATEGORIES = ['alley_rental', 'restroom_rental', 'eatery_rental'];

    /**
     * 1. Daily Collection Report -- rollup of daily_dues by route.
     */
    public function dailyCollectionReport(string $date): array
    {
        $dues = DailyDue::with(['collector', 'member'])
            ->onDate($date)
            ->get();

        $byRoute = $dues->groupBy('route')->map(function ($group, $route) {
            return [
                'route' => $route,
                'ticket_count' => (int) $group->sum('ticket_quantity'),
                'transactions' => $group->count(),
                'total_collected' => (float) $group->sum('amount_paid'),
                'total_savings' => (float) $group->sum('savings_share'),
                'total_rebate' => (float) $group->sum('rebate_share'),
                'total_association' => (float) $group->sum('association_share'),
            ];
        })->values();

        return [
            'date' => $date,
            'by_route' => $byRoute,
            'total_transactions' => $dues->count(),
            'grand_total' => (float) $dues->sum('amount_paid'),
        ];
    }

    /**
     * 2. Daily Cash Position / Closing Report.
     *
     * NOTE: uses Benefit status 'released' (the actual enum value in the
     * benefits table). ReportController::daily() currently filters on
     * 'paid', which is not a valid benefit status and will always return
     * zero -- worth fixing there too.
     */
    public function dailyCashPosition(string $date): array
    {
        $duesIncome = (float) DailyDue::onDate($date)->sum('amount_paid');
        $otherIncome = (float) IncomeExpense::whereDate('transaction_date', $date)->income()->sum('amount');
        $expenses = (float) IncomeExpense::whereDate('transaction_date', $date)->expense()->sum('amount');
        $benefitsPaid = (float) Benefit::whereDate('processed_date', $date)->where('status', 'released')->sum('amount');
        $loansReleased = (float) Loan::whereDate('loan_date', $date)->active()->sum('amount');

        $totalIn = $duesIncome + $otherIncome;
        $totalOut = $expenses + $benefitsPaid + $loansReleased;

        return [
            'date' => $date,
            'dues_income' => $duesIncome,
            'other_income' => $otherIncome,
            'total_cash_in' => $totalIn,
            'expenses' => $expenses,
            'benefits_paid' => $benefitsPaid,
            'loans_released' => $loansReleased,
            'total_cash_out' => $totalOut,
            'net_cash_position' => $totalIn - $totalOut,
        ];
    }

    /**
     * 3. Diesel / Fuel Consumption Report (JSON).
     */
    public function fuelConsumptionReport(string $startDate, string $endDate): array
    {
        $records = FuelConsumption::with('member')
            ->whereBetween('consumption_date', [$startDate, $endDate])
            ->get();

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_liters' => (float) $records->sum('liters'),
            'total_amount' => (float) $records->sum('amount'),
            'total_rebate' => (float) $records->sum('total_rebate'),
            'total_coop_deposit' => (float) $records->sum('total_coop_deposit'),
            'records' => $records->map(fn ($r) => [
                'member' => $r->member->full_name ?? null,
                'consumption_date' => $r->consumption_date->toDateString(),
                'liters' => (float) $r->liters,
                'amount' => (float) $r->amount,
                'total_rebate' => (float) $r->total_rebate,
                'total_coop_deposit' => (float) $r->total_coop_deposit,
                'refill_station' => $r->refill_station,
            ])->values(),
        ];
    }

    /**
     * 4. Member Savings Ledger.
     */
    public function memberSavingsLedger(int $memberId): array
    {
        $member = Member::findOrFail($memberId);

        $entries = SavingsLedger::where('member_id', $memberId)
            ->orderBy('date')
            ->orderBy('ledger_id')
            ->get();

        return [
            'member' => [
                'id' => $member->id,
                'member_no' => $member->member_no,
                'name' => $member->full_name,
                'current_balance' => (float) $member->savings_balance,
            ],
            'entries' => $entries->map(fn ($e) => [
                'date' => $e->date->toDateString(),
                'source_type' => $e->source_type,
                'txn_type' => $e->txn_type,
                'amount' => (float) $e->amount,
                'running_balance' => (float) $e->running_balance,
                'remarks' => $e->remarks,
            ])->values(),
        ];
    }

    /**
     * 5. Member Statement of Account (one calendar year).
     */
    public function memberStatementOfAccount(int $memberId, int $year): array
    {
        $member = Member::findOrFail($memberId);

        $dues = DailyDue::where('member_id', $memberId)->whereYear('collection_date', $year)->get();
        $fuel = FuelConsumption::where('member_id', $memberId)->whereYear('consumption_date', $year)->get();
        $loans = Loan::where('member_id', $memberId)->whereYear('loan_date', $year)->with('payments')->get();
        $benefits = Benefit::where('member_id', $memberId)->whereYear('claim_date', $year)->get();
        $ledger = SavingsLedger::where('member_id', $memberId)->whereYear('date', $year)->orderBy('date')->get();

        return [
            'member' => [
                'id' => $member->id,
                'member_no' => $member->member_no,
                'name' => $member->full_name,
            ],
            'year' => $year,
            'daily_dues_total' => (float) $dues->sum('amount_paid'),
            'savings_contributed' => (float) $dues->sum('savings_share'),
            'fuel_total_liters' => (float) $fuel->sum('liters'),
            'fuel_rebate_earned' => (float) $fuel->sum('total_rebate'),
            'loans' => $loans->map(fn ($l) => [
                'amount' => (float) $l->amount,
                'balance' => (float) $l->balance,
                'status' => $l->status,
            ])->values(),
            'benefits' => $benefits->map(fn ($b) => [
                'type' => $b->benefit_type,
                'amount' => (float) $b->amount,
                'status' => $b->status,
            ])->values(),
            'savings_ledger_entries' => $ledger->count(),
            'ending_balance' => (float) ($ledger->last()->running_balance ?? $member->savings_balance),
        ];
    }

    /**
     * 6a. Rebate Pool Report -- per-member rebate accrued for the year,
     * minus whatever has already been released (savings_ledger rows
     * with source_type = 'rebate_release') for that same year.
     *
     * Requires the 'rebate_release' source_type -- see migration
     * 2026_08_12_000000_add_rebate_release_to_savings_ledger_source_type.
     */
    public function rebatePoolReport(int $year): array
    {
        $accrued = DailyDue::whereYear('collection_date', $year)
            ->select('member_id', DB::raw('SUM(rebate_share) as total_rebate'))
            ->groupBy('member_id')
            ->with('member')
            ->get();

        $released = SavingsLedger::whereYear('date', $year)
            ->where('source_type', 'rebate_release')
            ->select('member_id', DB::raw('SUM(amount) as total_released'))
            ->groupBy('member_id')
            ->pluck('total_released', 'member_id');

        return $accrued->map(function ($row) use ($released) {
            $releasedAmount = (float) ($released[$row->member_id] ?? 0);
            $total = (float) $row->total_rebate;

            return [
                'member_id' => $row->member_id,
                'member_name' => $row->member->full_name ?? null,
                'total_rebate' => $total,
                'released' => $releasedAmount,
                'pending' => $total - $releasedAmount,
            ];
        })->values()->all();
    }

    /**
     * 6b. Total rebate pool still pending release for the year.
     */
    public function rebatePoolTotalPending(int $year): float
    {
        return (float) collect($this->rebatePoolReport($year))->sum('pending');
    }

    /**
     * 7. Association Fund Report.
     */
    public function associationFundReport(string $startDate, string $endDate): array
    {
        $byRoute = DailyDue::whereBetween('collection_date', [$startDate, $endDate])
            ->select('route', DB::raw('SUM(association_share) as total'))
            ->groupBy('route')
            ->get();

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_association_fund' => (float) $byRoute->sum('total'),
            'by_route' => $byRoute->map(fn ($r) => [
                'route' => $r->route,
                'total' => (float) $r->total,
            ])->values(),
        ];
    }

    /**
     * 8. Coop Deposit Report -- funded from fuel_consumptions.total_coop_deposit.
     */
    public function coopDepositReport(string $startDate, string $endDate): array
    {
        $records = FuelConsumption::whereBetween('consumption_date', [$startDate, $endDate])->get();

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_coop_deposit' => (float) $records->sum('total_coop_deposit'),
            'total_liters' => (float) $records->sum('liters'),
            'transaction_count' => $records->count(),
        ];
    }

    /**
     * 9. Income Statement (P&L), grouped by income_expenses.category.
     */
    public function incomeStatement(string $startDate, string $endDate): array
    {
        $income = IncomeExpense::income()->betweenDates($startDate, $endDate)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        $expense = IncomeExpense::expense()->betweenDates($startDate, $endDate)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        $totalIncome = (float) $income->sum('total');
        $totalExpense = (float) $expense->sum('total');

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'income_by_category' => $income->map(fn ($r) => ['category' => $r->category, 'total' => (float) $r->total])->values(),
            'expense_by_category' => $expense->map(fn ($r) => ['category' => $r->category, 'total' => (float) $r->total])->values(),
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_income' => $totalIncome - $totalExpense,
        ];
    }

    /**
     * 10. Rental Income Report -- alley_rental, restroom_rental,
     * eatery_rental categories only (see RENTAL_CATEGORIES above).
     */
    public function rentalIncomeReport(string $startDate, string $endDate): array
    {
        $records = IncomeExpense::income()
            ->whereIn('category', self::RENTAL_CATEGORIES)
            ->betweenDates($startDate, $endDate)
            ->get();

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_rental_income' => (float) $records->sum('amount'),
            'by_category' => $records->groupBy('category')->map(fn ($g, $cat) => [
                'category' => $cat,
                'total' => (float) $g->sum('amount'),
            ])->values(),
        ];
    }

    /**
     * 11a. Benefits Utilization Report (detail rows).
     */
    public function benefitsUtilizationReport(string $startDate, string $endDate): array
    {
        return Benefit::with('member')
            ->whereBetween('claim_date', [$startDate, $endDate])
            ->get()
            ->map(fn ($b) => [
                'member' => $b->member->full_name ?? null,
                'benefit_type' => $b->benefit_type,
                'amount' => (float) $b->amount,
                'claim_date' => $b->claim_date->toDateString(),
                'status' => $b->status,
            ])->values()->all();
    }

    /**
     * 11b. Benefits Summary grouped by benefit_type.
     */
    public function benefitsSummaryByType(string $startDate, string $endDate): array
    {
        return Benefit::whereBetween('claim_date', [$startDate, $endDate])
            ->select('benefit_type', DB::raw('COUNT(*) as claim_count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('benefit_type')
            ->get()
            ->map(fn ($r) => [
                'benefit_type' => $r->benefit_type,
                'claim_count' => (int) $r->claim_count,
                'total_amount' => (float) $r->total_amount,
            ])->values()->all();
    }

    /**
     * 12. Loan Ledger / Aging Report -- active loans with an outstanding
     * balance, flagged overdue if past their due_date.
     */
    public function loanLedgerAgingReport(): array
    {
        return Loan::with('member')
            ->whereIn('status', ['approved', 'active'])
            ->where('balance', '>', 0)
            ->get()
            ->map(function ($loan) {
                $isOverdue = $loan->due_date && $loan->due_date->isPast();
                $daysOverdue = $isOverdue ? $loan->due_date->diffInDays(now()) : 0;

                return [
                    'member' => $loan->member->full_name ?? null,
                    'amount' => (float) $loan->amount,
                    'balance' => (float) $loan->balance,
                    'due_date' => $loan->due_date?->toDateString(),
                    'days_overdue' => $daysOverdue,
                    'status' => $isOverdue ? 'overdue' : 'current',
                ];
            })->values()->all();
    }

    /**
     * 13. Monthly Summary Report across a range of months.
     * $startMonth / $endMonth are validated as 'date' upstream, so any
     * parseable date within the target month works (e.g. '2026-01-01').
     */
    public function monthlySummaryReport(string $startMonth, string $endMonth): array
    {
        $cursor = Carbon::parse($startMonth)->startOfMonth();
        $end = Carbon::parse($endMonth)->startOfMonth();

        $months = [];

        while ($cursor <= $end) {
            $monthStart = $cursor->copy()->startOfMonth()->toDateString();
            $monthEnd = $cursor->copy()->endOfMonth()->toDateString();

            $duesTotal = (float) DailyDue::whereBetween('collection_date', [$monthStart, $monthEnd])->sum('amount_paid');
            $incomeTotal = (float) IncomeExpense::income()->betweenDates($monthStart, $monthEnd)->sum('amount');
            $expenseTotal = (float) IncomeExpense::expense()->betweenDates($monthStart, $monthEnd)->sum('amount');

            $months[] = [
                'month' => $cursor->format('Y-m'),
                'dues_collected' => $duesTotal,
                'other_income' => $incomeTotal,
                'expenses' => $expenseTotal,
                'net' => $duesTotal + $incomeTotal - $expenseTotal,
            ];

            $cursor->addMonth();
        }

        return $months;
    }

    /**
     * 14a. Annual Rebate Release Report (read-only preview).
     */
    public function annualRebateReleaseReport(int $year): array
    {
        return [
            'year' => $year,
            'members' => $this->rebatePoolReport($year),
            'total_pending' => $this->rebatePoolTotalPending($year),
        ];
    }

    /**
     * 14b. Actually release the pending rebate pool for the year --
     * writes one savings_ledger deposit row per member with a pending
     * balance, and credits it onto Member::savings_balance.
     *
     * Returns the number of members updated.
     */
    public function markRebatePoolReleased(int $year): int
    {
        $pending = collect($this->rebatePoolReport($year))
            ->filter(fn ($row) => $row['pending'] > 0);

        $updated = 0;

        DB::transaction(function () use ($pending, $year, &$updated) {
            foreach ($pending as $row) {
                $member = Member::find($row['member_id']);

                if (! $member) {
                    continue;
                }

                $newBalance = $member->savings_balance + $row['pending'];

                SavingsLedger::create([
                    'member_id' => $member->id,
                    'date' => now()->toDateString(),
                    'source_type' => 'rebate_release',
                    'txn_type' => 'deposit',
                    'amount' => $row['pending'],
                    'running_balance' => $newBalance,
                    'remarks' => "Annual rebate pool release for {$year}",
                ]);

                $member->increment('savings_balance', $row['pending']);
                $updated++;
            }
        });

        return $updated;
    }

    /**
     * 15. Simplified Trial Balance across the whole system.
     */
    public function simplifiedTrialBalance(): array
    {
        return [
            'total_member_savings' => (float) Member::sum('savings_balance'),
            'total_loans_outstanding' => (float) Loan::whereIn('status', ['approved', 'active'])->sum('balance'),
            'total_income' => (float) IncomeExpense::income()->sum('amount'),
            'total_expense' => (float) IncomeExpense::expense()->sum('amount'),
            'total_benefits_released' => (float) Benefit::where('status', 'released')->sum('amount'),
            'total_association_fund' => (float) DailyDue::sum('association_share'),
            'total_coop_deposit' => (float) FuelConsumption::sum('total_coop_deposit'),
        ];
    }
}
