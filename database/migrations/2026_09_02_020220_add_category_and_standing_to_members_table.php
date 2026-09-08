<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // 'member' (full member, entitled to benefits + dividend) or
            // 'non-member' (tracked, pays dues, but no benefits/dividend).
            $table->string('category')->default('member')->after('route');

            // Set while status = 'suspended'; the members:lift-expired-suspensions
            // command restores 'active' once this date has passed.
            $table->date('suspended_until')->nullable()->after('status');
        });

        // status was enum('active','inactive'); widen to a plain string so
        // 'suspended' and 'terminated' (set by violation sanctions) are valid
        // without a MySQL-only ALTER (keeps the SQLite test DB working).
        Schema::table('members', function (Blueprint $table) {
            $table->string('status')->default('inactive')->change();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['category', 'suspended_until']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('inactive')->change();
        });
    }
};
