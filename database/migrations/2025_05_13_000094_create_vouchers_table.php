<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by');
            $table->string('code')->unique();
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('devices');
            $table->unsignedBigInteger('used_devices');
            $table->boolean('is_compensation')->default(false);
            $table->boolean('status')->default(false);
            $table->string('used_by')->nullable();
            $table->timestamps();
        
            // Optional: if you have a packages table, add a foreign key
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
        });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
