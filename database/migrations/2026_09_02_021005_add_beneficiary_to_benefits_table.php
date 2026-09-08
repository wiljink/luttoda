<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('benefits', function (Blueprint $table) {
            // Who the claim is for: the member themselves, or one of their
            // registered dependents (different per-day rate applies).
            $table->string('beneficiary_type')->default('member')->after('member_id');
            $table->foreignId('member_dependent_id')->nullable()->after('beneficiary_type')
                ->constrained('member_dependents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('benefits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_dependent_id');
            $table->dropColumn('beneficiary_type');
        });
    }
};
