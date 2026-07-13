<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use App\Models\DailyDue;
use App\Models\FuelConsumption;
use App\Models\IncomeExpense;
use App\Models\Loan;
use App\Models\Member;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today()->toDateString();

        $stats = [
            'total_members' => Member::active()->count(),
            'today_collection' => DailyDue::onDate($today)->sum('amount_paid'),
            'today_collectors' => DailyDue::onDate($today)->distinct('collected_by')->count('collected_by'),
            'total_savings' => Member::sum('savings_balance'),
            'pending_loans' => Loan::where('status', 'pending')->count(),
            'active_loans_balance' => Loan::active()->sum('balance'),
            'pending_benefits' => Benefit::pending()->count(),
            'month_income' => IncomeExpense::income()
                ->whereMonth('transaction_date', now()->month)
                ->sum('amount'),
            'month_expense' => IncomeExpense::expense()
                ->whereMonth('transaction_date', now()->month)
                ->sum('amount'),
            'month_fuel_liters' => FuelConsumption::whereMonth('consumption_date', now()->month)->sum('liters'),
        ];

        $recentDues = DailyDue::with('member')->latest()->limit(10)->get();
        $recentLoans = Loan::with('member')->latest()->limit(5)->get();

        return view('dashboard.index', compact('stats', 'recentDues', 'recentLoans'));
    }
}
