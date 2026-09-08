<?php

namespace App\Http\Controllers;

use App\Models\FuelConsumption;
use App\Models\Member;
use App\Models\Setting;
use App\Services\FuelConsumptionService;
use Illuminate\Http\Request;

class FuelConsumptionController extends Controller
{
    public function __construct(private FuelConsumptionService $fuelService) {}

    private function dieselPrice(): float
    {
        return (float) Setting::get('diesel_price_per_liter', 87);
    }

    public function index(Request $request)
    {
        $records = FuelConsumption::with('member')
            ->when($request->from && $request->to, fn ($q) => $q->whereBetween('consumption_date', [$request->from, $request->to]))
            ->latest('consumption_date')
            ->paginate(30);

        return view('fuel.index', compact('records'));
    }

    public function create()
    {
        $members = Member::active()->orderBy('firstname')->get();

        return view('fuel.create', ['members' => $members, 'dieselPrice' => $this->dieselPrice()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'consumption_date' => 'required|date',
            'liters' => 'required|numeric|min:0.01',
            'amount' => 'nullable|numeric|min:0',
            'refill_station' => 'nullable|string|max:150',
        ]);

        $this->fuelService->record(
            member: Member::findOrFail($validated['member_id']),
            date: $validated['consumption_date'],
            liters: (float) $validated['liters'],
            // Blank -> FuelConsumptionService computes liters x diesel price.
            amount: $request->filled('amount') ? (float) $validated['amount'] : null,
            station: $validated['refill_station'] ?? null,
        );

        return redirect()->route('fuel.index')
            ->with('success', 'Fuel consumption recorded successfully.');
    }

    public function show(FuelConsumption $fuel)
    {
        return view('fuel.show', compact('fuel'));
    }

    public function edit(FuelConsumption $fuel)
    {
        return view('fuel.edit', ['fuel' => $fuel, 'dieselPrice' => $this->dieselPrice()]);
    }

    public function update(Request $request, FuelConsumption $fuel)
    {
        $validated = $request->validate([
            'liters' => 'required|numeric|min:0.01',
            'amount' => 'nullable|numeric|min:0',
            'refill_station' => 'nullable|string|max:150',
        ]);

        // Blank amount -> recompute from the current diesel price.
        $validated['amount'] = $request->filled('amount')
            ? (float) $validated['amount']
            : round((float) $validated['liters'] * $this->dieselPrice(), 2);

        $this->fuelService->updateRecord($fuel, $validated);

        return redirect()->route('fuel.index')->with('success', 'Record updated successfully.');
    }

    public function destroy(FuelConsumption $fuel)
    {
        $this->fuelService->reverse($fuel);

        return redirect()->route('fuel.index')->with('success', 'Record deleted successfully.');
    }
}
