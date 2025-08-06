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
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('created_by');
            $table->string('TransID');
            $table->string('TransactionType');
            $table->string('TransTime');
            $table->decimal('TransAmount', 10, 2);
            $table->string('BusinessShortCode');
            $table->string('BillRefNumber');
            $table->decimal('OrgAccountBalance', 10, 2);
            $table->string('MSISDN');
            $table->string('FirstName');
            $table->boolean('status')->default(false);
            $table->string('site');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};
