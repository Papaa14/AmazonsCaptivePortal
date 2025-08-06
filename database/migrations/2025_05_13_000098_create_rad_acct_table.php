<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rad_acct', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('acctsessionid', 64)->default('');
            $table->string('username', 64)->default('');
            $table->string('realm', 128)->default('');
            $table->string('nasid', 32)->default('');
            $table->string('nasipaddress', 15)->default('');
            $table->string('nasportid', 32)->nullable();
            $table->string('nasporttype', 32)->nullable();
            $table->string('framedipaddress', 15)->default('');
            $table->bigInteger('acctinputoctets')->default(0);
            $table->bigInteger('acctoutputoctets')->default(0);
            $table->string('acctstatustype', 32)->nullable();
            $table->string('macaddr', 50);
            $table->integer('created_by');
            $table->timestamp('dateAdded')->default(DB::raw('CURRENT_TIMESTAMP'));

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rad_acct');
    }
}; 