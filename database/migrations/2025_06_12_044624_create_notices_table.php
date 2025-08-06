<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->boolean('superadmin_only')->default(false); // true: only superadmin sees
            $table->date('start_date')->nullable();  // Optional show range
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // Optional for multi-tenant
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
