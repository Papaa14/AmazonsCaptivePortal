<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'customers', function (Blueprint $table){
                $table->bigIncrements('id');
                $table->integer('customer_id');
                $table->integer('parent_id');
                $table->boolean('inherit_expiry')->default(0);
                $table->string('fullname')->nullable();
                $table->string('username')->nullable();
                $table->string('account')->nullable();
                $table->string('email')->nullable();
                $table->timestamp('extension_start')->nullable();
                $table->timestamp('extension_expiry')->nullable();
                $table->boolean('is_extended')->default(false);
                $table->string('contact')->nullable();
                $table->string('avatar', 100)->default('');
                $table->integer('created_by')->default(0);
                $table->integer('is_active')->default(1);
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->string('service')->nullable();
                $table->integer('auto_renewal')->default(1);
                $table->string('mac_address')->nullable();
                $table->integer('maclock')->default(1);
                $table->string('site')->nullable();
                $table->bigInteger('package_id')->nullable();
                $table->string('charges')->nullable();
                $table->text('package')->nullable();
                $table->string('apartment')->nullable();
                $table->string('location')->nullable();
                $table->string('housenumber')->nullable();
                $table->dateTime('expiry')->nullable();
                $table->string('status')->nullable();
                $table->string('lang')->default('en');
                $table->float('balance')->default(0);
                $table->integer('corporate')->default(0);
                $table->integer('connectionstatus')->default(1);
                $table->boolean('is_suspended')->default(0);
                $table->dateTime('suspended_at')->nullable();
                $table->string('override_download')->nullable();
                $table->string('override_download_unit')->nullable();
                $table->string('override_upload')->nullable();
                $table->string('override_upload_unit')->nullable();
                $table->boolean('is_override')->default(0);
                $table->dateTime('last_seen')->nullable();
                $table->rememberToken();
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
        Schema::dropIfExists('customers');
    }
}

