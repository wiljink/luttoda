<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alkansiya Program — a voluntary (non-compulsory) SSS savings pot a
 * member may top up alongside the daily collection. Tracked entirely
 * separately from the regular savings ledger / savings_balance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alkansiya_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('daily_due_id')->nullable()->constrained('daily_dues')->nullOnDelete();
            $table->date('contribution_date');
            $table->decimal('amount', 10, 2);
            $table->foreignId('collected_by')->nullable()->constrained('users');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'contribution_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alkansiya_contributions');
    }
};
