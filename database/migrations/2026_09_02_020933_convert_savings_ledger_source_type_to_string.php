<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * savings_ledger.source_type started as a MySQL enum and was widened
 * twice with raw ALTER statements (rebate_release, benefit_release) that
 * SQLite cannot run. Convert it to a plain string for every driver so:
 *   - the test DB (SQLite) has a working, open-ended column, and
 *   - future values (dividend_release) need no schema change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('savings_ledger', function (Blueprint $table) {
            $table->string('source_type')->change();
        });
    }

    public function down(): void
    {
        // Intentionally not restoring the enum — the string column is a
        // strict superset and the app validates values in code.
    }
};
