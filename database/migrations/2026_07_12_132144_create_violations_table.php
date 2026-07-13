<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->date('violation_date');
            $table->string('type');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'violation_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('violations');
    }
};
