<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
         Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->string('ticket_number')->unique();
        $table->enum('route', ['Carmen', 'Cogon'])->nullable();
        $table->enum('status', ['available', 'used'])->default('available');
        $table->foreignId('daily_due_id')->nullable()->constrained('daily_dues')->nullOnDelete();
        $table->date('used_on')->nullable();
        $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
