<?php

use App\Http\Controllers\BenefitController;
use App\Http\Controllers\DailyDuesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FuelConsumptionController;
use App\Http\Controllers\IncomeExpenseController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ViolationController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

// Route::get('/', function () {
//     return redirect()->route('dashboard');
// });

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('members', MemberController::class);
    Route::resource('daily-dues', DailyDuesController::class);
    Route::resource('fuel', FuelConsumptionController::class);
    Route::resource('income-expenses', IncomeExpenseController::class);
    Route::resource('benefits', BenefitController::class)->except(['edit', 'update']);
    Route::resource('loans', LoanController::class)->except(['edit', 'update']);
    Route::resource('members.violations', ViolationController::class)
    ->except(['show']);

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


    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('daily', [ReportController::class, 'daily'])->name('daily');
        Route::get('monthly', [ReportController::class, 'monthly'])->name('monthly');
        Route::get('member/{member}', [ReportController::class, 'memberLedger'])->name('member');
        Route::get('fuel', [ReportController::class, 'fuelConsumption'])->name('fuel');
        Route::get('export/{type}', [ReportController::class, 'export'])->name('export');
    });


});
