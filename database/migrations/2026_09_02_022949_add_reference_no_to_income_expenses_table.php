<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('income_expenses', function (Blueprint $table) {
            // Control / OR / reference number from imported expense and
            // rental sheets.
            $table->string('reference_no')->nullable()->after('description');
            $table->text('remarks')->nullable()->after('reference_no');
        });
    }

    public function down(): void
    {
        Schema::table('income_expenses', function (Blueprint $table) {
            $table->dropColumn(['reference_no', 'remarks']);
        });
    }
};
