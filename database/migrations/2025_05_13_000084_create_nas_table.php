<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('nas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by');
            $table->string('nasname');
            $table->string('shortname')->nullable();
            $table->string('secret');
            $table->boolean('nasapi')->default(0);
            $table->string('type')->default('other');
            $table->string('server')->default('radius');
            $table->string('community')->default('');
            $table->string('description')->default('Dynamically added NAS');
            $table->unsignedBigInteger('api_port')->default(8728);
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('nas');
    }
};
