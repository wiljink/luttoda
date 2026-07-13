<?php

namespace App\Http\Controllers;

use App\Models\IncomeExpense;
use Illuminate\Http\Request;

class IncomeExpenseController extends Controller
{
    public function index(Request $request)
    {
        $records = IncomeExpense::query()
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->from && $request->to, fn($q) => $q->betweenDates($request->from, $request->to))
            ->latest('transaction_date')
            ->paginate(30);

        $totalIncome = IncomeExpense::income()->sum('amount');
        $totalExpense = IncomeExpense::expense()->sum('amount');

        return view('income-expenses.index', compact('records', 'totalIncome', 'totalExpense'));
    }

    public function create()
    {
        return view('income-expenses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'type' => 'required|in:income,expense',
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
        ]);

        IncomeExpense::create(array_merge($validated, [
            'recorded_by' => auth()->id(),
        ]));

        return redirect()->route('income-expenses.index')
            ->with('success', 'Narekord ang transaksyon.');
    }

    public function show(IncomeExpense $incomeExpense)
    {
        return view('income-expenses.show', compact('incomeExpense'));
    }

    public function edit(IncomeExpense $incomeExpense)
    {
        return view('income-expenses.edit', compact('incomeExpense'));
    }

    public function update(Request $request, IncomeExpense $incomeExpense)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $incomeExpense->update($validated);

        return redirect()->route('income-expenses.index')->with('success', 'Na-update.');
    }

    public function destroy(IncomeExpense $incomeExpense)
    {
        $incomeExpense->delete();
        return redirect()->route('income-expenses.index')->with('success', 'Natangtang.');
    }
}
