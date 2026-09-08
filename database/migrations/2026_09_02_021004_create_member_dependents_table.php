<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship');
            $table->date('birthdate')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['member_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_dependents');
    }
};
