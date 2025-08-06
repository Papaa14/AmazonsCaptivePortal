<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->string('ticket_id')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('status')->default('pending'); // pending, assigned, in_progress, completed, converted, cancelled
            $table->string('priority')->default('normal'); // pending, assigned, in_progress, completed, converted, cancelled
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->date('installation_date')->nullable();
            $table->time('installation_time')->nullable();
            $table->timestamp('completion_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('location')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('technician_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickets');
    }
}
