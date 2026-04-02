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
        Schema::create('user_plan_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->char('ref_id', 40)->nullable();
            $table->string('gateway', 50)->nullable();
            $table->text('logs')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0 = pending, 1 = success, 2 = cancelled');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_plan_logs');
    }
};
