<?php

namespace App\Services;

use App\Models\FuelConsumption;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Single write path for a fuel-consumption record and the net fuel-rebate
 * deposit it produces (total_rebate - total_coop_deposit), shared by
 * FuelConsumptionController and the Excel importer.
 */
class FuelConsumptionService
{
    public function __construct(private SavingsLedgerService $ledger) {}

    private function rebatePerLiter(): float
    {
        return (float) Setting::get('fuel_rebate_per_liter', 3.00);
    }

    private function coopDepositPerLiter(): float
    {
        return (float) Setting::get('coop_deposit_per_liter', 1.00);
    }

    /**
     * @param  float|null  $amount  null -> liters * diesel_price_per_liter setting
     */
    public function record(
        Member $member,
        string $date,
        float $liters,
        ?float $amount = null,
        ?string $station = null,
    ): FuelConsumption {
        return DB::transaction(function () use ($member, $date, $liters, $amount, $station) {
            $fuel = FuelConsumption::create([
                'member_id' => $member->id,
                'consumption_date' => $date,
                'liters' => $liters,
                'amount' => $amount ?? ($liters * (float) Setting::get('diesel_price_per_liter', 87)),
                'refill_station' => $station,
                'rebate_per_liter' => $this->rebatePerLiter(),
                'coop_deposit_per_liter' => $this->coopDepositPerLiter(),
            ]);

            // Generated columns (total_rebate / total_coop_deposit) aren't
            // populated on the in-memory model from create().
            $fuel->refresh();

            $this->ledger->record(
                member: $member,
                date: $fuel->consumption_date,
                sourceType: 'fuel_rebate',
                txnType: 'deposit',
                amount: $fuel->total_rebate - $fuel->total_coop_deposit,
                sourceable: $fuel,
                remarks: "Fuel rebate - {$fuel->liters}L",
            );

            return $fuel;
        });
    }

    /**
     * Apply an edit and write a correcting ledger entry only if the net
     * rebate actually changed.
     */
    public function updateRecord(FuelConsumption $fuel, array $data): FuelConsumption
    {
        return DB::transaction(function () use ($fuel, $data) {
            $oldNet = $fuel->total_rebate - $fuel->total_coop_deposit;

            $fuel->update($data);
            $fuel->refresh();

            $delta = ($fuel->total_rebate - $fuel->total_coop_deposit) - $oldNet;

            if (abs($delta) > 0.001) {
                $this->ledger->record(
                    member: $fuel->member,
                    date: today()->toDateString(),
                    sourceType: 'adjustment',
                    txnType: $delta > 0 ? 'deposit' : 'withdrawal',
                    amount: abs($delta),
                    sourceable: $fuel,
                    remarks: "Correction - fuel log #{$fuel->id} liters updated to {$fuel->liters}L",
                );
            }

            return $fuel;
        });
    }

    public function reverse(FuelConsumption $fuel): void
    {
        DB::transaction(function () use ($fuel) {
            $this->ledger->record(
                member: $fuel->member,
                date: today()->toDateString(),
                sourceType: 'adjustment',
                txnType: 'withdrawal',
                amount: $fuel->total_rebate - $fuel->total_coop_deposit,
                remarks: "Reversal - deleted fuel log #{$fuel->id} ({$fuel->liters}L on {$fuel->consumption_date->toDateString()})",
            );

            $fuel->delete();
        });
    }
}
