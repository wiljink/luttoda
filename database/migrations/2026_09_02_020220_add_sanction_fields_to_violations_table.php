<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violations', function (Blueprint $table) {
            // null = record only, no sanction. Otherwise one of
            // suspension | termination | dismembership.
            $table->string('sanction')->nullable()->after('type');

            // End date for a suspension sanction.
            $table->date('sanction_until')->nullable()->after('sanction');

            // Stamped when members:lift-expired-suspensions reactivates the
            // member this suspension applied to.
            $table->timestamp('sanction_lifted_at')->nullable()->after('sanction_until');
        });
    }

    public function down(): void
    {
        Schema::table('violations', function (Blueprint $table) {
            $table->dropColumn(['sanction', 'sanction_until', 'sanction_lifted_at']);
        });
    }
};
