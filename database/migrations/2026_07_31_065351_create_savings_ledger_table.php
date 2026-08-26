<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('savings_ledger', function (Blueprint $table) {
            $table->id('ledger_id');
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->date('date');
            $table->enum('source_type', ['daily_dues', 'fuel_rebate', 'loan_deduction', 'withdrawal', 'adjustment']);
            $table->enum('txn_type', ['deposit', 'withdrawal']);
            $table->decimal('amount', 12, 2);
            $table->decimal('running_balance', 12, 2);

            // Polymorphic-ish reference back to the source record (daily_due,
            // fuel_consumption, loan, etc.) so you can trace where a ledger
            // entry came from without guessing by date/amount.
            $table->nullableMorphs('sourceable');

            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('savings_ledger');
    }
};
