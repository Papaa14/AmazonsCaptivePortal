<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('active_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->string('mac_address')->index();
            $table->timestamps();

            // A device can only have one session per subscription.
            $table->unique(['subscription_id', 'mac_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_sessions');
    }
};