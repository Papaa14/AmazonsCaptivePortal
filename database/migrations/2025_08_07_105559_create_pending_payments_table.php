<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('hotspot_package_id')->constrained();
            
            // This is the unique reference we generate ourselves.
            $table->string('internal_ref')->unique(); 
            
            // This is the reference we get back from the aggregator's API.
            $table->string('payment_reference')->nullable()->index(); 
            
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_payments');
    }
};