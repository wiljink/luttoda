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
        Schema::create('loans', function (Blueprint $table) {
               $table->id();
                $table->foreignId('member_id')->constrained();
                $table->decimal('amount', 10, 2);
                $table->decimal('interest_rate', 5, 2)->default(0);
                $table->decimal('total_payable', 10, 2);
                $table->decimal('balance', 10, 2);
                $table->date('loan_date');
                $table->date('due_date')->nullable();
                $table->enum('status', ['pending', 'approved', 'active', 'paid', 'rejected'])->default('pending');
                $table->text('purpose')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users');
                $table->timestamps();
            });

        Schema::create('loan_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
                $table->date('payment_date');
                $table->decimal('amount', 10, 2);
                $table->foreignId('received_by')->constrained('users');
                $table->timestamps();
            });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
