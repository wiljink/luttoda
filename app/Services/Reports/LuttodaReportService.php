<?php

namespace App\Services\Reports;

use App\Models\Benefit;
use App\Models\DailyDue;
use App\Models\FuelConsumption;
use App\Models\IncomeExpense;
use App\Models\Loan;
use App\Models\Member;
use App\Models\SavingsLedger;
use App\Models\Setting;
use App\Services\SavingsLedgerService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $alkansiya = \App\Models\AlkansiyaContribution::where('member_id', $memberId)
            ->whereYear('contribution_date', $year)
            ->orderBy('contribution_date')
            ->get();

        return [
            'member' => [
                'id' => $member->id,
                'member_no' => $member->member_no,
                'name' => $member->full_name,
                'route' => $member->route,
                'plate_number' => $member->plate_number,
                'category' => $member->category,
            ],
            'year' => $year,
            'days_paid' => $dues->count(),
            'tickets_total' => (int) $dues->sum('ticket_quantity'),
            'daily_dues_total' => (float) $dues->sum('amount_paid'),
            'savings_contributed' => (float) $dues->sum('savings_share'),
            'members_share_contributed' => (float) $dues->sum('rebate_share'),
            'association_contributed' => (float) $dues->sum('association_share'),
            'fuel_total_liters' => (float) $fuel->sum('liters'),
            'fuel_rebate_earned' => (float) $fuel->sum('total_rebate'),
            'alkansiya_total' => (float) $alkansiya->sum('amount'),
            'alkansiya_balance' => (float) $member->alkansiya_balance,
            'alkansiya_entries' => $alkansiya->map(fn ($a) => [
                'date' => $a->contribution_date->toDateString(),
                'amount' => (float) $a->amount,
                'remarks' => $a->remarks,
            ])->values(),
            'loans' => $loans->map(fn ($l) => [
                'date' => $l->loan_date->toDateString(),
                'type' => $l->type,
                'amount' => (float) $l->amount,
                'total_payable' => (float) $l->total_payable,
                'paid' => (float) $l->payments->sum('amount'),
                'balance' => (float) $l->balance,
                'status' => $l->status,
            ])->values(),
            'benefits' => $benefits->map(fn ($b) => [
                'type' => $b->benefit_type,
                'amount' => (float) $b->amount,
                'status' => $b->status,
            ])->values(),
            'savings_ledger_entries' => $ledger->count(),
            'savings_balance' => (float) $member->savings_balance,
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
     * 6d. Annual Savings Return -- of the ₱50 daily due, ₱35 (savings) plus
     * ₱7.50 (member's share / rebate) is paid back in cash to the member
     * once a year (default 30 November). The remaining ₱7.50 is association
     * income and is kept.
     *
     * entitlement = SUM(daily_dues.savings_share) + SUM(daily_dues.rebate_share) for the year
     * returned    = SUM(savings_ledger.amount WHERE source_type='savings_return') for the year
     * pending     = entitlement - returned
     */
    public function savingsReturnReport(int $year): array
    {
        $dues = DailyDue::whereYear('collection_date', $year)
            ->select('member_id', DB::raw('SUM(savings_share) as savings'), DB::raw('SUM(rebate_share) as share'))
            ->groupBy('member_id')
            ->with('member')
            ->get();

        $returned = SavingsLedger::whereYear('date', $year)
            ->where('source_type', 'savings_return')
            ->select('member_id', DB::raw('SUM(amount) as total'))
            ->groupBy('member_id')
            ->pluck('total', 'member_id');

        $shareReleased = SavingsLedger::whereYear('date', $year)
            ->where('source_type', 'rebate_release')
            ->select('member_id', DB::raw('SUM(amount) as total'))
            ->groupBy('member_id')
            ->pluck('total', 'member_id');

        return $dues->map(function ($row) use ($returned, $shareReleased) {
            $savings = (float) $row->savings;
            $share = (float) $row->share;
            $entitlement = round($savings + $share, 2);
            $paid = (float) ($returned[$row->member_id] ?? 0);

            return [
                'member_id' => $row->member_id,
                'member_name' => $row->member->full_name ?? null,
                'savings' => $savings,
                'share' => $share,
                'share_released' => (float) ($shareReleased[$row->member_id] ?? 0),
                'entitlement' => $entitlement,
                'returned' => $paid,
                'pending' => round($entitlement - $paid, 2),
            ];
        })->values()->all();
    }

    public function savingsReturnTotalPending(int $year): float
    {
        return (float) collect($this->savingsReturnReport($year))->sum('pending');
    }

    /** Date the return is paid on (from settings, default 30 November). */
    public function savingsReturnDate(int $year): Carbon
    {
        $month = max(1, min(12, (int) Setting::get('savings_return_month', 11)));
        $day = (int) Setting::get('savings_return_day', 30);

        $date = Carbon::create($year, $month, 1);

        return $date->day(min($day, $date->daysInMonth));
    }

    /**
     * Pay out the pending annual savings return for the year. For each
     * member with a pending amount:
     *   1. bring any un-released ₱7.50 share into savings (rebate_release deposit)
     *   2. withdraw the full entitlement in cash (savings_return withdrawal)
     * Both dated the configured savings-return date. Idempotent.
     *
     * Returns the number of members paid.
     */
    public function markSavingsReturnReleased(int $year): int
    {
        $rows = collect($this->savingsReturnReport($year))->filter(fn ($r) => $r['pending'] > 0);

        $ledger = app(SavingsLedgerService::class);
        $date = $this->savingsReturnDate($year)->toDateString();
        $paid = 0;

        DB::transaction(function () use ($rows, $year, $ledger, $date, &$paid) {
            foreach ($rows as $row) {
                $member = Member::find($row['member_id']);
                if (! $member) {
                    continue;
                }

                // 1. Un-released member's share -> into savings first.
                $unreleasedShare = round($row['share'] - $row['share_released'], 2);
                if ($unreleasedShare > 0) {
                    $ledger->record(
                        member: $member,
                        date: $date,
                        sourceType: 'rebate_release',
                        txnType: 'deposit',
                        amount: $unreleasedShare,
                        remarks: "Member's share for {$year} (annual savings return)",
                    );
                }

                // 2. Pay out the full entitlement in cash.
                $ledger->record(
                    member: $member,
                    date: $date,
                    sourceType: 'savings_return',
                    txnType: 'withdrawal',
                    amount: $row['pending'],
                    remarks: "Annual savings return for {$year} (₱35 savings + ₱7.50 share per ticket)",
                );
                $paid++;
            }
        });

        return $paid;
    }

    /**
     * 6c. Annual Dividend Rebate -- separate from the daily-dues rebate
     * pool (6a) and the per-liter fuel rebate. Every member gets 50% of
     * their diesel value for the year returned to them (released each
     * December). Non-members ARE included -- only benefit *claims* are
     * member-only. Terminated members are excluded.
     *
     *   dividend = (liters for the year * diesel_price_per_liter) / 2
     *
     * minus whatever has already been released (savings_ledger rows with
     * source_type = 'dividend_release') for that year.
     */
    public function dividendRebateReport(int $year): array
    {
        $dieselPrice = (float) Setting::get('diesel_price_per_liter', 87);

        $liters = FuelConsumption::whereYear('consumption_date', $year)
            ->select('member_id', DB::raw('SUM(liters) as total_liters'))
            ->groupBy('member_id')
            ->pluck('total_liters', 'member_id');

        $released = SavingsLedger::whereYear('date', $year)
            ->where('source_type', 'dividend_release')
            ->select('member_id', DB::raw('SUM(amount) as total_released'))
            ->groupBy('member_id')
            ->pluck('total_released', 'member_id');

        return Member::query()
            ->where('status', '!=', 'terminated')
            ->whereIn('id', $liters->keys())
            ->orderBy('firstname')
            ->get()
            ->map(function (Member $member) use ($liters, $released, $dieselPrice) {
                $totalLiters = (float) ($liters[$member->id] ?? 0);
                $dividend = round($totalLiters * $dieselPrice / 2, 2);
                $releasedAmount = (float) ($released[$member->id] ?? 0);

                return [
                    'member_id' => $member->id,
                    'member_name' => $member->full_name,
                    'total_liters' => $totalLiters,
                    'diesel_price' => $dieselPrice,
                    'dividend' => $dividend,
                    'released' => $releasedAmount,
                    'pending' => round($dividend - $releasedAmount, 2),
                ];
            })
            ->values()
            ->all();
    }

    public function dividendRebateTotalPending(int $year): float
    {
        return (float) collect($this->dividendRebateReport($year))->sum('pending');
    }

    /**
     * Release the pending annual dividend for the year. Unlike
     * markRebatePoolReleased(), this goes through SavingsLedgerService so
     * running_balance and Member::savings_balance stay consistent with
     * every other ledger writer. Idempotent -- a second run releases 0.
     *
     * Returns the number of members credited.
     */
    public function markDividendReleased(int $year): int
    {
        $pending = collect($this->dividendRebateReport($year))
            ->filter(fn ($row) => $row['pending'] > 0);

        $ledger = app(SavingsLedgerService::class);
        $releaseDate = min(Carbon::now(), Carbon::create($year, 12, 31))->toDateString();
        $updated = 0;

        DB::transaction(function () use ($pending, $year, $ledger, $releaseDate, &$updated) {
            foreach ($pending as $row) {
                $member = Member::find($row['member_id']);
                if (! $member) {
                    continue;
                }

                $ledger->record(
                    member: $member,
                    date: $releaseDate,
                    sourceType: 'dividend_release',
                    txnType: 'deposit',
                    amount: $row['pending'],
                    remarks: "Annual dividend rebate for {$year}",
                );
                $updated++;
            }
        });

        return $updated;
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
     * 10b. Collections Income Report -- every income_expenses row of
     * type 'income' for the period (rental, dispatcher fee, parking fee,
     * tricab rental, other collections, ...), grouped by category with a
     * subtotal per category and a grand total. Groups dynamically, so
     * imported categories show up without a code change.
     */
    public function collectionsIncomeReport(string $startDate, string $endDate): array
    {
        $records = IncomeExpense::income()
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $byCategory = $records
            ->groupBy('category')
            ->map(fn ($group, $category) => [
                'category' => $category,
                'label' => Str::of((string) $category)->replace('_', ' ')->title()->value(),
                'count' => $group->count(),
                'total' => (float) $group->sum('amount'),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'grand_total' => (float) $records->sum('amount'),
            'transaction_count' => $records->count(),
            'by_category' => $byCategory,
            'transactions' => $records,
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
