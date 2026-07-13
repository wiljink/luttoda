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
        Schema::create('income_expenses', function (Blueprint $table) {
           $table->id();
            $table->date('transaction_date');
            $table->enum('type', ['income', 'expense']);
            $table->enum('category', [
                // Income categories
                'alley_rental', 'restroom_rental', 'eatery_rental',
                'lechon_manok', 'barbershop', 'fruit_stand', 'other_income',
                // Expense categories
                'operational', 'maintenance', 'salaries', 'other_expense'
            ]);
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_expenses');
    }
};
