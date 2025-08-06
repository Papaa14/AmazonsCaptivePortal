<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('company_id')->nullable();
            $table->string('email')->unique();
            $table->string('owner')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->integer('plan')->nullable();
            $table->integer('extra_customers')->nullable();
            $table->date('plan_expire_date')->nullable();
            $table->integer('requested_plan')->default(0);
            $table->integer('trial_plan')->default(0);
            $table->date('trial_expire_date')->nullable();
            $table->string('type', 100)->nullable();
            $table->integer('phone_number')->nullable();
            $table->string('avatar')->default('avatar.png');
            $table->string('location', 100)->nullable();
            $table->boolean('active_status')->default(0);
            $table->integer('delete_status')->default(1);
            $table->string('mode', 10)->default('light');
            $table->boolean('dark_mode')->default(0);
            $table->integer('is_disable')->default(1);
            $table->integer('is_enable_login')->default(1);
            $table->integer('is_active')->default(1);
            $table->integer('referral_code')->default(0);
            $table->integer('used_referral_code')->default(0);
            $table->integer('commission_amount')->default(0);
            $table->datetime('last_login_at')->nullable();
            $table->integer('created_by')->default(0);
            $table->rememberToken();
            $table->timestamps();
            $table->boolean('is_email_verified')->default(0);
            $table->string('pppoe_pay')->nullable();
            $table->string('hotspot_pay')->nullable();
            $table->integer('is_system_api_enable')->default(1);
            $table->json('payment_settings')->nullable();
            $table->boolean('has_payment_settings')->default(0);
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
        Schema::dropIfExists('users');
    }
}
