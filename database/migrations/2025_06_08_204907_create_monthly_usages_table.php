<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthlyUsagesTable extends Migration
{
    public function up()
    {
        Schema::create('monthly_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('created_by'); // tenant
            $table->unsignedInteger('year');
            $table->unsignedTinyInteger('month');
            $table->bigInteger('upload')->default(0);   // bytes
            $table->bigInteger('download')->default(0); // bytes
            $table->timestamps();

            $table->unique(['customer_id', 'year', 'month']);
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('monthly_usages');
    }
}
