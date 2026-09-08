<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * income_expenses.category was a fixed MySQL enum. The rental import maps
 * open-ended business / rental-type names to category slugs, so widen it
 * to a plain string (portable across MySQL and the SQLite test DB).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('income_expenses', function (Blueprint $table) {
            $table->string('category')->change();
        });
    }

    public function down(): void
    {
        // Not restoring the enum -- string is a superset.
    }
};
