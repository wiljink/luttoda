<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds 'rebate_release' as a valid source_type on savings_ledger.
 *
 * Needed so ReportController::releaseRebatePool() (via
 * LuttodaReportService::markRebatePoolReleased()) can write a
 * savings_ledger deposit row when the annual rebate pool for a member
 * is released into their savings balance.
 *
 * Existing source_type values were:
 *   'daily_dues', 'fuel_rebate', 'loan_deduction', 'withdrawal', 'adjustment'
 */
return new class extends Migration
{
    public function up(): void
    {
        // Raw MySQL enum widening. SQLite (test DB) has no ENUM and would
        // choke on this; a later migration converts source_type to a
        // plain string for every driver, so skipping it here is safe.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

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

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // NOTE: rolling back will fail if any rows already use
        // 'rebate_release' -- clean those up first if you ever need to
        // reverse this migration.
        DB::statement("
            ALTER TABLE savings_ledger
            MODIFY source_type ENUM(
                'daily_dues',
                'fuel_rebate',
                'loan_deduction',
                'withdrawal',
                'adjustment'
            ) NOT NULL
        ");
    }
};
