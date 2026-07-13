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
        Schema::create('daily_dues', function (Blueprint $table) {
           $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number')->nullable();
            $table->date('collection_date');
            $table->enum('route', ['Carmen', 'Cogon']);
            $table->decimal('amount_paid', 8, 2)->default(50.00);
            // Auto-split: ₱50 → ₱35 savings + ₱7.50 rebate + ₱7.50 assoc
            $table->decimal('savings_share', 8, 2)->default(35.00);
            $table->decimal('rebate_share', 8, 2)->default(7.50);
            $table->decimal('association_share', 8, 2)->default(7.50);
            $table->foreignId('collected_by')->constrained('users');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_dues');
    }
};
