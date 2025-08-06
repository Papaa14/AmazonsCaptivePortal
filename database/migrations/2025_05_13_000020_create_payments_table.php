<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'payments', function (Blueprint $table){
            $table->id();
            $table->foreignId(column: 'created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('payment_reference')->nullable(); // M-Pesa txn code
            $table->enum('payment_method', ['mpesa', 'cash', 'card', 'bank', 'voucher'])->default('mpesa');
            $table->decimal('amount', 10, 2);
            $table->timestamp('paid_at');
            $table->enum('status', ['successful', 'pending', 'failed'])->default('successful');
            $table->text('notes')->nullable();
            $table->timestamps();
        }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
