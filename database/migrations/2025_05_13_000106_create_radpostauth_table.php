<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('radpostauth', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedBigInteger('created_by');
            $table->string('username', 64)->default('');
            $table->string('pass', 64)->default('');
            $table->text('reply')->nullable();
            $table->string('nasipaddress')->nullable();
            $table->string('nasportid')->nullable();
            $table->string('mac')->nullable();
            $table->string('acctuniqueid')->nullable();
            $table->string('auth_type')->nullable();
            $table->timestamp('authdate', 6)->useCurrent()->useCurrentOnUpdate();
            $table->string('class', 64)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('radpostauth');
    }
};
