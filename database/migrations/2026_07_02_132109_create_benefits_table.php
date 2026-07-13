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
        Schema::create('benefits', function (Blueprint $table) {
               $table->id();
                $table->foreignId('member_id')->constrained();
                $table->enum('benefit_type', ['hospitalization', 'burial', 'sss']);
                // Amounts: hospitalization=₱500/day, burial=₱3000, SSS=₱700 savings
                $table->decimal('amount', 10, 2);
                $table->date('claim_date');
                $table->integer('days')->nullable(); // for hospitalization
                $table->enum('status', ['pending', 'approved', 'released', 'rejected'])->default('pending');
                $table->text('remarks')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users');
                $table->date('processed_date')->nullable();
                $table->timestamps();
            });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benefits');
    }
};
