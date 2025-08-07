<?php
// filename: database/migrations/YYYY_MM_DD_HHMMSS_create_hotspot_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the table to store information about devices (users)
 * that have connected to the hotspot.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * This method creates the table and its columns.
     */
    public function up(): void
    {
        Schema::create('hotspot_users', function (Blueprint $table) {
          
            $table->id();           
            $table->string('phone_number');  
            $table->string('otp') ;               
            $table->timestamp('expires_at')->nullable();          
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * This method is executed when you run 'php artisan migrate:rollback'.
     * It safely drops the table.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspot_users');
    }
};