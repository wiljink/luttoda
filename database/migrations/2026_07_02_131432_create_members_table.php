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
        Schema::create('members', function (Blueprint $table) {
               $table->id();
                $table->string('member_no')->unique();
                $table->string('firstname');
                $table->string('lastname');
                $table->string('middlename')->nullable();
                $table->string('plate_number')->unique();
                $table->string('operator_name');
                $table->enum('route', ['Carmen', 'Cogon']);
                $table->string('contact_number')->nullable();
                $table->string('address')->nullable();
                $table->date('date_joined');
                $table->enum('status', ['active', 'inactive'])->default('inactive');
                $table->decimal('savings_balance', 10, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });

        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
