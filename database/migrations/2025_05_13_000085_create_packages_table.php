<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by');
            $table->string('name_plan');
            $table->integer('price');
            $table->string('type');
            $table->string('typebp');
            $table->boolean('is_limited')->default(0);
            $table->integer('time_limit')->nullable();
            $table->string('time_unit')->nullable();
            $table->integer('data_limit')->nullable();
            $table->string('data_unit')->nullable();
            $table->integer('validity');
            $table->string('validity_unit');
            $table->integer('shared_users');
            $table->integer('tax_value')->nullable();
            $table->string('tax_type')->nullable();
            $table->integer('fup_limit')->nullable();
            $table->string('fup_unit')->nullable();
            $table->integer('fup_down_speed')->nullable();
            $table->string('fup_down_unit')->nullable();
            $table->integer('fup_up_speed')->nullable();
            $table->string('fup_up_unit')->nullable();
            $table->boolean('fup_limit_status')->default(0);
            $table->string('device')->nullable();
            $table->json('assigned_to')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('packages');
    }
};
