<?php

namespace App\Http\Controllers;

use App\Models\FuelConsumption;
use App\Models\Member;
use App\Services\SavingsLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FuelConsumptionController extends Controller
{
    // ₱3/liter total rebate, of which ₱1/liter goes to the coop deposit
    // fund (see LUTTODA process flow, module 3). The remainder
    // (rebate - coop_deposit) is what actually lands in member savings.
    private const REBATE_PER_LITER = 3.00;
    private const COOP_DEPOSIT_PER_LITER = 1.00;

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

        // NEW: rate columns were previously never set, so the generated
        // columns (total_rebate, total_coop_deposit) always computed to 0.
        $fuel = FuelConsumption::create(array_merge($validated, [
            'rebate_per_liter' => self::REBATE_PER_LITER,
            'coop_deposit_per_liter' => self::COOP_DEPOSIT_PER_LITER,
        ]));

        // Generated/stored columns aren't populated on the in-memory model
        // returned by create() -- reload from the DB to get their values.
        $fuel->refresh();

        app(SavingsLedgerService::class)->record(
            member: $fuel->member,
            date: $fuel->consumption_date,
            sourceType: 'fuel_rebate',
            txnType: 'deposit',
            amount: $fuel->total_rebate - $fuel->total_coop_deposit,
            sourceable: $fuel,
            remarks: "Fuel rebate - {$fuel->liters}L",
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
        return view('fuel.edit', compact('fuel'));
    }

    public function update(Request $request, FuelConsumption $fuel)
    {
        $validated = $request->validate([
            'liters' => 'required|numeric|min:0.01',
            'amount' => 'required|numeric|min:0',
            'refill_station' => 'nullable|string|max:150',
        ]);

        DB::transaction(function () use ($validated, $fuel) {
            $oldNetRebate = $fuel->total_rebate - $fuel->total_coop_deposit;

            $fuel->update($validated);
            $fuel->refresh(); // reload generated columns at the new liters value

            $delta = ($fuel->total_rebate - $fuel->total_coop_deposit) - $oldNetRebate;

            // Only write a correcting ledger entry if the rebate actually
            // changed -- e.g. refill_station-only edits shouldn't touch it.
            if (abs($delta) > 0.001) {
                app(SavingsLedgerService::class)->record(
                    member: $fuel->member,
                    date: today()->toDateString(),
                    sourceType: 'adjustment',
                    txnType: $delta > 0 ? 'deposit' : 'withdrawal',
                    amount: abs($delta),
                    sourceable: $fuel,
                    remarks: "Correction - fuel log #{$fuel->id} liters updated to {$fuel->liters}L",
                );
            }
        });

        return redirect()->route('fuel.index')->with('success', 'Record updated successfully.');
    }

    public function destroy(FuelConsumption $fuel)
    {
        DB::transaction(function () use ($fuel) {
            // Reverse the deposit this fuel log generated in store() before
            // removing it, so savings_ledger stays an accurate audit trail.
            app(SavingsLedgerService::class)->record(
                member: $fuel->member,
                date: today()->toDateString(),
                sourceType: 'adjustment',
                txnType: 'withdrawal',
                amount: $fuel->total_rebate - $fuel->total_coop_deposit,
                remarks: "Reversal - deleted fuel log #{$fuel->id} ({$fuel->liters}L on {$fuel->consumption_date->toDateString()})",
            );

            $fuel->delete();
        });

        return redirect()->route('fuel.index')->with('success', 'Record deleted successfully.');
    }
}
