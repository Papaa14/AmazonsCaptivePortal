<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotspot_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Sh29= 1GB + 500MB bonus valid 24Hours"
            $table->text('description')->nullable(); // A more user-friendly description
            $table->decimal('price', 8, 2);
            
            // Duration in minutes for flexibility (e.g., 30, 60, 1440 for 24 hours)
            $table->unsignedInteger('duration_minutes'); 
            
            $table->boolean('is_unlimited')->default(false);

            // Data caps in Megabytes (MB)
            $table->unsignedBigInteger('data_limit_mb')->nullable();
            $table->unsignedBigInteger('bonus_data_mb')->nullable();

            $table->unsignedInteger('device_limit')->default(1);
            $table->boolean('is_free')->default(false)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspot_packages');
    }
};