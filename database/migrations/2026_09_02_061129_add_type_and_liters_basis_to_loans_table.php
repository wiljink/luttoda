<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // 'cash' (default) or 'diesel'. A diesel loan's amount is
            // auto-computed from the member's un-borrowed diesel litres.
            $table->string('type')->default('cash')->after('member_id');

            // Litres of diesel this diesel loan was drawn against, so the
            // same litres can't back a second loan.
            $table->decimal('liters_basis', 10, 2)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['type', 'liters_basis']);
        });
    }
};
