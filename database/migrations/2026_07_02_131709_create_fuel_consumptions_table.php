<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fuel_consumptions', function (Blueprint $table) {
               $table->id();
                $table->foreignId('member_id')->constrained();
                $table->date('consumption_date');
                $table->decimal('liters', 8, 2);
                $table->decimal('amount', 10, 2);
                $table->decimal('rebate_per_liter', 5, 2)->default(3.00);
                $table->decimal('coop_deposit_per_liter', 5, 2)->default(1.00);
                // Total rebate = liters * 3, coop deposit = liters * 1
                $table->decimal('total_rebate', 10, 2)->storedAs('liters * rebate_per_liter');
                $table->decimal('total_coop_deposit', 10, 2)->storedAs('liters * coop_deposit_per_liter');
                $table->string('refill_station')->nullable();
                $table->timestamps();
            });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_consumptions');
    }
};
