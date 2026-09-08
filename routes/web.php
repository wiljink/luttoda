<?php

use App\Http\Controllers\BenefitController;
use App\Http\Controllers\DailyDuesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FuelConsumptionController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\IncomeExpenseController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberDependentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ViolationController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {

    // Dashboard — visible to everyone logged in
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ADDED: ProfileController existed but was never wired up. These are
    // the standard Breeze profile routes (edit/update/delete account),
    // referenced by the default Breeze navigation dropdown.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------
    | Admin-only routes
    |--------------------------------------------------------------------
    | Members, Loans, Benefits, Income & Expenses, Violations, Tickets, Users
    */
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('members', MemberController::class);
        Route::resource('income-expenses', IncomeExpenseController::class);
        Route::get('benefits/eligibility', [BenefitController::class, 'eligibility'])->name('benefits.eligibility');
        Route::resource('benefits', BenefitController::class)->except(['edit', 'update']);
        Route::resource('loans', LoanController::class)->except(['edit', 'update']);
        Route::resource('members.violations', ViolationController::class)->except(['show']);
        Route::resource('members.dependents', MemberDependentController::class)->only(['store', 'update', 'destroy']);

        // Loan special actions
        Route::post('loans/{loan}/approve', [LoanController::class, 'approve'])->name('loans.approve');
        Route::post('loans/{loan}/reject', [LoanController::class, 'reject'])->name('loans.reject');
        Route::post('loans/{loan}/payment', [LoanController::class, 'addPayment'])->name('loans.payment');

        // Benefits approval/release
        Route::post('benefits/{benefit}/approve', [BenefitController::class, 'approve'])->name('benefits.approve');
        Route::post('benefits/{benefit}/release', [BenefitController::class, 'release'])->name('benefits.release');

        // Tickets
        Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/edit-range', [TicketController::class, 'editRange'])->name('tickets.editRange');
        Route::put('/tickets/bulk-update-route', [TicketController::class, 'bulkUpdateRoute'])->name('tickets.bulkUpdateRoute');
        Route::delete('/tickets/bulk-destroy', [TicketController::class, 'bulkDestroy'])->name('tickets.bulkDestroy');
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');

        // ADDED: single-ticket edit/update/destroy -- TicketController
        // already defined these methods but no route pointed to them.
        Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
        Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
        Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');

        // User Management
        Route::resource('users', UserController::class)->except(['show']);

        // System Settings (business rules)
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        // Excel template import (members / daily collection / expenses / rental)
        Route::get('import', [ImportController::class, 'index'])->name('import.index');
        Route::post('import', [ImportController::class, 'store'])->name('import.store');
    });

    /*
    |--------------------------------------------------------------------
    | Admin + Collector routes
    |--------------------------------------------------------------------
    | Daily Dues, Fuel Consumption
    */
    Route::middleware(['role:admin|collector'])->group(function () {
        Route::resource('daily-dues', DailyDuesController::class);
        Route::resource('fuel', FuelConsumptionController::class);
    });

    /*
    |--------------------------------------------------------------------
    | Admin + Accounting routes
    |--------------------------------------------------------------------
    | Reports
    */
    Route::middleware(['role:admin|accounting'])->prefix('reports')->name('reports.')->group(function () {
        Route::get('daily', [ReportController::class, 'daily'])->name('daily');
        Route::get('monthly', [ReportController::class, 'monthly'])->name('monthly');

        // Member savings ledger: search/picker page first (no member ID
        // needed), since the sidebar can't link straight into a route
        // that requires {member}. Placed above member/{member} so
        // "ledger" doesn't get swallowed by that wildcard segment.
        Route::get('ledger', [ReportController::class, 'ledgerSearch'])->name('ledger.search');

        Route::get('member/{member}', [ReportController::class, 'memberLedger'])->name('member');
        Route::get('member/{member}/export', [ReportController::class, 'exportMemberLedger'])->name('member.export');
        Route::get('member/{member}/statement.pdf', [ReportController::class, 'exportMemberStatement'])->name('member.statement.pdf');

        Route::get('fuel', [ReportController::class, 'fuelConsumption'])->name('fuel');
        Route::get('export/{type}', [ReportController::class, 'export'])->name('export');

        /*
        |----------------------------------------------------------------
        | Luttoda reports
        |----------------------------------------------------------------
        */
        Route::get('daily-collection', [ReportController::class, 'dailyCollection'])
            ->name('daily-collection');

        Route::get('daily-cash-position', [ReportController::class, 'dailyCashPosition'])
            ->name('daily-cash-position');

        // ADDED: was defined on the controller (renamed from
        // fuelConsumption() to avoid the collision with the view-based
        // one above) but had no route pointing to it.
        Route::get('fuel-consumption-report', [ReportController::class, 'fuelConsumptionReport'])
            ->name('fuel-consumption-report');

        // ADDED: no route previously registered.
        Route::get('member/{memberId}/savings-ledger', [ReportController::class, 'memberSavingsLedger'])
            ->whereNumber('memberId')
            ->name('member.savings-ledger');

        // ADDED: no route previously registered.
        Route::get('member/{memberId}/statement', [ReportController::class, 'memberStatement'])
            ->whereNumber('memberId')
            ->name('member.statement');

        Route::get('rebate-pool', [ReportController::class, 'rebatePool'])
            ->name('rebate-pool');

        // ADDED: HTML page versions for the sidebar (the routes above stay
        // as JSON API endpoints). Placed as distinct paths/names so
        // neither breaks the other.
        Route::get('rebate-pool/view', [ReportController::class, 'rebatePoolPage'])
            ->name('rebate-pool.page');

        // Annual Savings Return: ₱35 savings + ₱7.50 share per ticket paid
        // back in cash once a year (default 30 November).
        Route::get('savings-return', [ReportController::class, 'savingsReturn'])
            ->name('savings-return');
        Route::get('savings-return/view', [ReportController::class, 'savingsReturnPage'])
            ->name('savings-return.page');
        Route::post('savings-return/{year}/release', [ReportController::class, 'releaseSavingsReturn'])
            ->whereNumber('year')
            ->name('savings-return.release');

        // Annual Dividend Rebate: (member diesel liters x diesel price) / 2,
        // released once a year into member savings.
        Route::get('dividend-rebate', [ReportController::class, 'dividendRebate'])
            ->name('dividend-rebate');
        Route::get('dividend-rebate/view', [ReportController::class, 'dividendRebatePage'])
            ->name('dividend-rebate.page');
        Route::post('dividend-rebate/{year}/release', [ReportController::class, 'releaseDividend'])
            ->whereNumber('year')
            ->name('dividend-rebate.release');

        Route::get('association-fund', [ReportController::class, 'associationFund'])
            ->name('association-fund');

        Route::get('association-fund/view', [ReportController::class, 'associationFundPage'])
            ->name('association-fund.page');

        Route::get('coop-deposit', [ReportController::class, 'coopDeposit'])
            ->name('coop-deposit');

        Route::get('coop-deposit/view', [ReportController::class, 'coopDepositPage'])
            ->name('coop-deposit.page');

        Route::get('income-statement', [ReportController::class, 'incomeStatement'])
            ->name('income-statement');

        Route::get('rental-income', [ReportController::class, 'rentalIncome'])
            ->name('rental-income');

        // Collections / Rental Income (HTML page + JSON), grouped by category.
        Route::get('collections-income', [ReportController::class, 'collectionsIncome'])
            ->name('collections-income');
        Route::get('collections-income/view', [ReportController::class, 'collectionsIncomePage'])
            ->name('collections-income.page');

        Route::get('benefits-utilization', [ReportController::class, 'benefitsUtilization'])
            ->name('benefits-utilization');

        Route::get('loan-ledger-aging', [ReportController::class, 'loanLedgerAging'])
            ->name('loan-ledger-aging');

        Route::get('monthly-summary', [ReportController::class, 'monthlySummary'])
            ->name('monthly-summary');

        Route::get('annual-rebate-release', [ReportController::class, 'annualRebateRelease'])
            ->name('annual-rebate-release');

        Route::post('annual-rebate-release/{year}/release', [ReportController::class, 'releaseRebatePool'])
            ->name('annual-rebate-release.release');

        Route::get('trial-balance', [ReportController::class, 'trialBalance'])
            ->name('trial-balance');
    });

});
