<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Cached running total of the member's voluntary Alkansiya
            // (SSS) contributions. Written only by App\Services\AlkansiyaService.
            $table->decimal('alkansiya_balance', 12, 2)->default(0)->after('savings_balance');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('alkansiya_balance');
        });
    }
};
