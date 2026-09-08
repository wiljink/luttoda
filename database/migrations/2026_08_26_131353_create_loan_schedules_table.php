<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('installment_no');
            $table->date('due_date');
            $table->decimal('principal_due', 10, 2);
            $table->decimal('interest_due', 10, 2);
            $table->decimal('penalty_due', 10, 2)->default(0);
            $table->decimal('principal_paid', 10, 2)->default(0);
            $table->decimal('interest_paid', 10, 2)->default(0);
            $table->decimal('penalty_paid', 10, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
            $table->timestamps();
        });

        $this->backfillExistingLoans();
    }

    /**
     * The loan schedule system replaces the old single-balance model.
     * Existing (dev-data) loans predate schedules, so give each a single
     * installment #1 mirroring what it already has, otherwise they'd show
     * an empty amortization table and penalties could never be assessed.
     */
    private function backfillExistingLoans(): void
    {
        $loans = DB::table('loans')->whereIn('status', ['approved', 'active', 'paid'])->get();

        foreach ($loans as $loan) {
            $interestDue = round($loan->total_payable - $loan->amount, 2);
            $paidSoFar = round($loan->total_payable - $loan->balance, 2);
            $interestPaid = min($interestDue, $paidSoFar);
            $principalPaid = round($paidSoFar - $interestPaid, 2);

            DB::table('loan_schedules')->insert([
                'loan_id' => $loan->id,
                'installment_no' => 1,
                'due_date' => $loan->due_date ?? $loan->loan_date,
                'principal_due' => $loan->amount,
                'interest_due' => $interestDue,
                'penalty_due' => 0,
                'principal_paid' => $principalPaid,
                'interest_paid' => $interestPaid,
                'penalty_paid' => 0,
                'status' => $loan->status === 'paid' ? 'paid' : ($paidSoFar > 0 ? 'partial' : 'pending'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_schedules');
    }
};
