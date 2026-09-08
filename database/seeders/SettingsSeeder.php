<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Business-rule defaults. Idempotent: only the row's meta (type,
     * group, label) is kept in sync on re-seed — an admin-edited `value`
     * is never overwritten.
     */
    public const DEFAULTS = [
        // group, key, value, type, label
        ['fuel', 'diesel_price_per_liter', '87', 'float', 'Current diesel price per liter (₱) — auto-fills the fuel amount and drives the annual dividend. Update whenever the pump price changes.'],
        ['fuel', 'fuel_rebate_per_liter', '3.00', 'float', 'Fuel rebate per liter (₱)'],
        ['fuel', 'coop_deposit_per_liter', '1.00', 'float', 'Coop deposit per liter (₱)'],

        ['dues', 'dues_price_per_ticket', '50.00', 'float', 'Daily due price per ticket (₱)'],
        ['dues', 'dues_savings_per_ticket', '35.00', 'float', 'Savings share per ticket (₱) — returned to the member on the annual savings-return date'],
        ['dues', 'dues_rebate_per_ticket', '7.50', 'float', "Member's share per ticket (₱) — returned with the savings on the annual savings-return date"],
        ['dues', 'dues_association_per_ticket', '7.50', 'float', 'Association-fund share per ticket (₱) — kept by the association'],
        ['dues', 'savings_return_month', '11', 'int', 'Annual savings-return month (1-12, e.g. 11 = November)'],
        ['dues', 'savings_return_day', '30', 'int', 'Annual savings-return day of month'],
        ['dues', 'ticket_start_number', '1001', 'int', 'First ticket number the system assigns (on a fresh system). Once tickets exist it just continues from the highest number on file.'],

        ['benefits', 'benefit_primary_daily', '500', 'float', 'Primary member benefit per day (₱)'],
        ['benefits', 'benefit_dependent_daily', '300', 'float', 'Dependent benefit per day (₱)'],
        ['benefits', 'benefit_burial', '3000', 'float', 'Burial benefit (₱, flat)'],
        ['benefits', 'benefit_sss', '700', 'float', 'SSS benefit (₱, flat)'],
        ['benefits', 'benefit_max_days_per_year', '10', 'int', 'Max benefit days per year per person (principal and each beneficiary)'],

        ['eligibility', 'eligibility_tickets_a', '75', 'int', 'Eligibility threshold — tickets (A)'],
        ['eligibility', 'eligibility_diesel_liters', '500', 'float', 'Eligibility threshold — diesel liters'],
        ['eligibility', 'eligibility_tickets_b', '150', 'int', 'Eligibility threshold — tickets (B)'],

        ['loans', 'diesel_loan_liter_factor', '2', 'float', 'Diesel loan — litre factor (amount = un-borrowed litres × factor × percentage)'],
        ['loans', 'diesel_loan_percentage', '0.80', 'float', 'Diesel loan — percentage (e.g. 0.80 = 80%)'],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as [$group, $key, $value, $type, $label]) {
            $existing = Setting::query()->where('key', $key)->first();

            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $existing?->value ?? $value,
                    'type' => $type,
                    'group' => $group,
                    'label' => $label,
                ],
            );
        }

        Setting::flushCache();
    }
}
