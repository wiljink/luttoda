<?php

namespace App\Http\Controllers;

use App\Models\FuelConsumption;
use App\Models\Member;
use Illuminate\Http\Request;

class FuelConsumptionController extends Controller
{
    public function index(Request $request)
    {
        $records = FuelConsumption::with('member')
            ->when($request->from && $request->to, fn($q) => $q->whereBetween('consumption_date', [$request->from, $request->to]))
            ->latest('consumption_date')
            ->paginate(30);

        return view('fuel.index', compact('records'));
    }

    public function create()
    {
        $members = Member::active()->orderBy('firstname')->get();
        return view('fuel.create', compact('members'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'consumption_date' => 'required|date',
            'liters' => 'required|numeric|min:0.01',
            'amount' => 'required|numeric|min:0',
            'refill_station' => 'nullable|string|max:150',
        ]);

        FuelConsumption::create($validated);

        return redirect()->route('fuel.index')
            ->with('success', 'Fuel consumption recorded successfully.');
    }

    public function show(FuelConsumption $fuel)
    {
        return view('fuel.show', compact('fuel'));
    }

    public function edit(FuelConsumption $fuel)
    {
        return view('fuel.edit', compact('fuel'));
    }

    public function update(Request $request, FuelConsumption $fuel)
    {
        $validated = $request->validate([
            'liters' => 'required|numeric|min:0.01',
            'amount' => 'required|numeric|min:0',
            'refill_station' => 'nullable|string|max:150',
        ]);

        $fuel->update($validated);

        return redirect()->route('fuel.index')->with('success', 'Record updated successfully.');
    }

    public function destroy(FuelConsumption $fuel)
    {
        $fuel->delete();
        return redirect()->route('fuel.index')->with('success', 'Record deleted successfully.');
    }
}
