<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('system_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('plan_id');
            $table->string('checkout');
            $table->string('company_id');
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string(column: 'currency')->default('KES');
            $table->text('description')->nullable();
            $table->text('phone')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_transactions');
    }
};
