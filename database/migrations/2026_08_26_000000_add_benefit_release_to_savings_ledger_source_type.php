<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds 'benefit_release' as a valid source_type on savings_ledger.
 *
 * Needed so BenefitController::release() can write a savings_ledger
 * deposit row (via SavingsLedgerService) when an SSS benefit claim is
 * released into a member's savings balance, instead of incrementing
 * Member::savings_balance directly with no audit trail.
 *
 * Existing source_type values were:
 *   'daily_dues', 'fuel_rebate', 'loan_deduction', 'withdrawal',
 *   'adjustment', 'rebate_release'
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE savings_ledger
            MODIFY source_type ENUM(
                'daily_dues',
                'fuel_rebate',
                'loan_deduction',
                'withdrawal',
                'adjustment',
                'rebate_release',
                'benefit_release'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        // NOTE: rolling back will fail if any rows already use
        // 'benefit_release' -- clean those up first if you ever need to
        // reverse this migration.
        DB::statement("
            ALTER TABLE savings_ledger
            MODIFY source_type ENUM(
                'daily_dues',
                'fuel_rebate',
                'loan_deduction',
                'withdrawal',
                'adjustment',
                'rebate_release'
            ) NOT NULL
        ");
    }
};
