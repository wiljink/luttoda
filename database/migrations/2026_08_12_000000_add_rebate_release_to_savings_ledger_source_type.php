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
