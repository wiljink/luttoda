<?php

namespace App\Http\Controllers;

use App\Models\DailyDue;
use App\Models\FuelConsumption;
use App\Models\IncomeExpense;
use App\Models\Member;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function daily(Request $request)
    {
        $date = $request->get('date', today()->toDateString());

        $dues = DailyDue::with('member')
            ->onDate($date)
            ->get()
            ->groupBy('route');

        $income = IncomeExpense::whereDate('transaction_date', $date)->income()->sum('amount');
        $expense = IncomeExpense::whereDate('transaction_date', $date)->expense()->sum('amount');

        return view('reports.daily', compact('dues', 'income', 'expense', 'date'));
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
            'dues' => $member->dailyDues()->latest('collection_date')->get(),
            'loans' => $member->loans()->with('payments')->get(),
            'benefits' => $member->benefits()->get(),
            'fuel' => $member->fuelConsumptions()->get(),
        ]);
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
}
